<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\Database\DataReader\IDataReader;
use HalloWelt\MediaWiki\Lib\Migration\Database\DataWriter\IDataWriter;
use SplFileInfo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class CliWorkersCommandBase extends CliCommandBase {
	private const SELECT_TIMEOUT_SEC = 1;
	private const CHUNK = 65536;
	private const WORKER_PIPE_FD = 3;
	/** Number of trailing stderr lines kept per worker for the failure report. */
	private const STDERR_TAIL_LINES = 20;
	/** Number of replay errors reported per worker before the rest are only counted. */
	private const REPLAY_ERROR_REPORT_LIMIT = 5;
	/** Grace period for a child to be reaped after its pipes reached EOF. */
	private const REAP_ATTEMPTS = 50;
	private const REAP_INTERVAL_USEC = 20000;

	/** @var array<int,string[]> trailing stderr lines per worker index */
	private array $workerStderrTail = [];

	/** @var array<int,string[]> replay failures per worker index */
	private array $workerReplayErrors = [];

	/** @var array<int,int> total replay failure count per worker index */
	private array $workerReplayErrorCount = [];

	/**
	 * Shared data writer used by both direct execution and worker children.
	 *
	 * The concrete command decides what the storage backend is and how worker
	 * children should talk to it; the base class only keeps the active writer
	 * reference and coordinates the worker lifecycle around it.
	 */
	protected ?IDataWriter $dataWriter = null;

	/**
	 * Optional shared data reader used when a command needs to look up state
	 * from the same backing store that receives the writes.
	 *
	 * The base class does not assume every worker-aware command needs a reader,
	 * so the hook remains nullable and the derived command can opt in only when
	 * it has a real read-side dependency.
	 */
	protected ?IDataReader $dataReader = null;

	/**
	 * Add the worker-related CLI flags shared by worker-aware commands.
	 */
	protected function configure() {
		parent::configure();

		$definition = $this->getDefinition();
		$definition->addOption(
			new InputOption(
				'workers',
				null,
				InputOption::VALUE_REQUIRED,
				'Number of parallel worker processes to spawn (default: 1, no parallelism)',
				1
			)
		);
		// Internal flag: child workers receive a zero-based slice index from the orchestrator.
		$definition->addOption(
			new InputOption(
				'worker',
				null,
				InputOption::VALUE_REQUIRED,
				'[Internal] Zero-based index of this worker process'
			)
		);
	}

	/**
	 * True when the current process was started as a worker child.
	 *
	 * Worker children inherit the same command arguments as the parent but
	 * receive an additional `--worker` flag so the shared base can branch into
	 * the child-side execution path.
	 */
	protected function isWorkerProcess(): bool {
		return $this->input !== null && $this->input->hasParameterOption( '--worker' );
	}

	/**
	 * Return the total worker count requested by the caller.
	 *
	 * A missing input object is treated as a single-worker run so subclasses can
	 * safely call this during setup and from tests without needing a live CLI.
	 */
	protected function getWorkerCount(): int {
		return $this->input === null ? 1 : (int)$this->input->getOption( 'workers' );
	}

	/**
	 * Return the zero-based slice index assigned to the current child process.
	 *
	 * The parent process launches workers as `--worker=0`, `--worker=1`, ... so
	 * each child can keep only the file slice assigned to its index.
	 */
	protected function getWorkerIndex(): int {
		return $this->input === null ? 0 : (int)$this->input->getOption( 'worker' );
	}

	/**
	 * Reduce the file list to the slice owned by this worker.
	 *
	 * Commands that need extra filtering can call this after their own file
	 * selection logic so the worker split stays consistent in one place.
	 * The default strategy is simple round-robin partitioning by file order.
	 */
	protected function sliceFilesForCurrentWorker(): void {
		if ( !$this->isWorkerProcess() ) {
			return;
		}

		$workers = $this->getWorkerCount();
		$worker = $this->getWorkerIndex();

		$index = 0;
		$filtered = [];
		foreach ( $this->files as $path => $file ) {
			if ( $index % $workers === $worker ) {
				$filtered[$path] = $file;
			}
			$index++;
		}

		$this->files = $filtered;
	}

	/**
	 * Keep file-list creation in the inheritance chain so descendants can add
	 * their own filters and then slice the result for workers.
	 *
	 * This is intentionally a no-op wrapper around the grandparent method so
	 * child commands can call `parent::makeFileList()` and stay within the worker
	 * base instead of skipping straight to the batch processor implementation.
	 */
	protected function makeFileList(): void {
		parent::makeFileList();
	}

	/**
	 * Execute the command with shared worker orchestration.
	 *
	 * The subclasses only provide the storage-specific writers and the callback
	 * that performs the real command execution. The base class owns the process
	 * split, child spawning, pipe replay, and exit-code aggregation.
	 *
	 * @param callable():int $runCommand
	 */
	protected function executeWithWorkers(
		InputInterface $input,
		OutputInterface $output,
		callable $runCommand
	): int {
		$this->input = $input;
		$this->output = $output;

		$this->dest = realpath( $this->input->getOption( 'dest' ) );
		if ( !is_dir( $this->dest ) ) {
			$this->output->writeln( 'Destination does not exist' );
			return Command::FAILURE;
		}

		// Keep workspace initialisation consistent with CliCommandBase even when
		// the orchestrator path does not go through beforeProcessFiles().
		$this->workspace = new Workspace( new SplFileInfo( $this->dest ) );

		if ( $this->isWorkerProcess() ) {
			// Child workers only need the child-side writer; the orchestrator owns the storage.
			$this->dataReader = $this->getWorkerDataReader();
			$this->dataWriter = $this->getWorkerDataWriter();

			try {
				return $runCommand();
			} finally {
				$this->dataReader = null;
				$this->dataWriter = null;
			}
		}

		$workers = $this->getWorkerCount();
		$this->dataReader = $this->getDataReader();
		$this->dataWriter = $this->getDataWriter();

		if ( $workers > 1 ) {
			// Multi-worker runs keep the command process as the parent collector and
			// fan child output back into the single direct writer.
			$this->initExecutionTime();
			try {
				$result = $this->runParallelWorkers( $this->output, $this->dataWriter, $workers );
				$this->logExecutionTime();

				return $result;
			} finally {
				$this->dataReader = null;
				$this->dataWriter = null;
			}
		}

		try {
			return $runCommand();
		} finally {
			$this->dataReader = null;
			$this->dataWriter = null;
		}
	}

	/**
	 * Skip worker-local execution-time logging so only the orchestrator reports
	 * one overall runtime for the full command.
	 */
	protected function afterProcessFiles(): void {
		if ( $this->isWorkerProcess() ) {
			return;
		}

		parent::afterProcessFiles();
	}

	/**
	 * Spawn the requested number of worker children and replay their pipe output
	 * into the shared writer.
	 *
	 * Worker stdout/stderr is forwarded to the parent output stream while the DB
	 * pipe is line-buffered and replayed into the parent data writer.
	 */
	protected function runParallelWorkers(
		OutputInterface $output,
		IDataWriter $dataWriter,
		int $workers
	): int {
		$descriptors = [
			0 => [ 'pipe', 'r' ],
			1 => [ 'pipe', 'w' ],
			2 => [ 'pipe', 'w' ],
			self::WORKER_PIPE_FD => [ 'pipe', 'w' ],
		];

		$procs = [];
		$exit = [];
		$openPipes = [];
		$streams = [];
		$buffers = [];

		$this->workerStderrTail = [];
		$this->workerReplayErrors = [];
		$this->workerReplayErrorCount = [];

		for ( $i = 0; $i < $workers; $i++ ) {
			$pipes = [];
			// proc_open() is used intentionally so the child can inherit the exact
			// command line while adding only its worker slice index.
			$proc = proc_open( [ ...self::baseCommandFromArgv(), '--worker=' . $i ], $descriptors, $pipes );
			if ( $proc === false ) {
				$output->writeln( "<error>Failed to start worker {$i}.</error>" );
				$this->terminateWorkers( $procs );

				return Command::FAILURE;
			}

			$output->writeln( "Starting worker {$i}" );

			fclose( $pipes[0] );
			foreach ( [ 1, 2, self::WORKER_PIPE_FD ] as $fd ) {
				stream_set_blocking( $pipes[$fd], false );
			}

			$procs[$i] = $proc;
			$exit[$i] = null;
			$openPipes[$i] = 3;
			$buffers[$i] = '';
			$this->workerStderrTail[$i] = [];
			$this->workerReplayErrors[$i] = [];
			$this->workerReplayErrorCount[$i] = 0;

			$streams[ (int)$pipes[1] ] = [ $i, 'out', $pipes[1] ];
			$streams[ (int)$pipes[2] ] = [ $i, 'err', $pipes[2] ];
			$streams[ (int)$pipes[ self::WORKER_PIPE_FD ] ] = [ $i, 'db', $pipes[ self::WORKER_PIPE_FD ] ];
		}

		// Read whichever child is ready next and replay the structured DB stream
		// into the parent writer until every pipe reaches EOF.
		while ( $streams !== [] ) {
			$read = array_map( static fn ( array $streamInfo ) => $streamInfo[2], $streams );
			$write = null;
			$except = null;

			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- stream_select warns on EINTR.
			$ready = @stream_select( $read, $write, $except, self::SELECT_TIMEOUT_SEC );
			if ( $ready === false ) {
				continue;
			}

			foreach ( $read as $stream ) {
				$key = (int)$stream;
				[ $i, $kind ] = $streams[$key];

				$chunk = fread( $stream, self::CHUNK );
				if ( $chunk !== false && $chunk !== '' ) {
					if ( $kind === 'db' ) {
						$buffers[$i] .= $chunk;
						$this->replayBufferedWorkerOutput( $buffers[$i], $dataWriter, $i );
					} else {
						if ( $kind === 'err' ) {
							$this->rememberWorkerStderr( $i, $chunk );
						}
						$this->forwardWorkerOutput( $output, $i, $kind, $chunk );
					}
				}

				if ( feof( $stream ) ) {
					if ( $kind === 'db' ) {
						$this->replayBufferedWorkerOutput( $buffers[$i], $dataWriter, $i, true );
						$buffers[$i] = '';
					}
					fclose( $stream );
					unset( $streams[$key] );

					if ( --$openPipes[$i] === 0 ) {
						$status = $this->reapWorker( $procs[$i] );
						$exit[$i] = $status['exitcode'];
						$this->reportWorkerResult( $output, $i, $status );
					}
				}
			}
		}

		$failed = array_keys(
			array_filter( $exit, static fn ( $code ) => $code !== Command::SUCCESS )
		);
		if ( $failed !== [] ) {
			$output->writeln( '<error>Workers failed: ' . implode( ', ', $failed ) . '</error>' );
			$output->writeln(
				'<comment>Re-run the same command without --workers to reproduce the failure '
				. 'with a readable stack trace.</comment>'
			);

			return Command::FAILURE;
		}

		$replayFailures = array_sum( $this->workerReplayErrorCount );
		if ( $replayFailures > 0 ) {
			$output->writeln(
				"<error>All workers exited cleanly, but {$replayFailures} record(s) could not be "
				. 'written to storage. The result is incomplete.</error>'
			);

			return Command::FAILURE;
		}

		$output->writeln( '<info>All workers completed successfully.</info>' );

		return Command::SUCCESS;
	}

	/**
	 * Rebuild the current command line so worker children inherit the same
	 * arguments, minus any pre-existing worker slice.
	 * 
	 * We rebuild the current PHP invocation from $_SERVER['argv'] so the parent process
	 * can spawn child workers with the same arguments.
	 * At that point there is no InputInterface object to read from,
	 * and using one would couple worker spawning to Symfony’s parsed input state instead
	 * of the actual argv that launched the process.
	 *
	 * @return string[]
	 */
	public static function baseCommandFromArgv(): array {
		$argv = $_SERVER['argv'];
		$cmd = [ PHP_BINARY, $argv[0] ];

		for ( $i = 1, $n = count( $argv ); $i < $n; $i++ ) {
			$arg = $argv[$i];
			if ( preg_match( '#^--worker(=.*)?$#', $arg ) ) {
				if ( $arg === '--worker' ) {
					$i++;
				}
				continue;
			}
			$cmd[] = $arg;
		}

		return $cmd;
	}

	/**
	 * Wait for a child whose pipes reached EOF and collect its termination details.
	 *
	 * proc_close() alone cannot tell a non-zero exit apart from a signal kill, so the
	 * status is read first and the process is closed afterwards.
	 *
	 * @param resource $proc
	 * @return array{exitcode:int,signaled:bool,termsig:int}
	 */
	private function reapWorker( $proc ): array {
		$status = proc_get_status( $proc );
		// The child can still be reported as running for a moment after its pipes closed.
		for ( $attempt = 0; $status['running'] && $attempt < self::REAP_ATTEMPTS; $attempt++ ) {
			usleep( self::REAP_INTERVAL_USEC );
			$status = proc_get_status( $proc );
		}

		$wasRunning = $status['running'];
		$closeCode = proc_close( $proc );

		return [
			'exitcode' => $wasRunning ? $closeCode : (int)$status['exitcode'],
			'signaled' => (bool)( $status['signaled'] ?? false ),
			'termsig' => (int)( $status['termsig'] ?? 0 ),
		];
	}

	/**
	 * Print a self-contained report for one finished worker.
	 *
	 * A worker that dies mid-run leaves nothing behind on the CLI otherwise, so the
	 * cause, the captured stderr tail and any unapplied records are printed together.
	 *
	 * @param array{exitcode:int,signaled:bool,termsig:int} $status
	 */
	private function reportWorkerResult( OutputInterface $output, int $worker, array $status ): void {
		$replayErrorCount = $this->workerReplayErrorCount[$worker] ?? 0;

		if ( $status['exitcode'] === Command::SUCCESS && !$status['signaled'] ) {
			$output->writeln( "<info>Worker {$worker} finished successfully.</info>" );
			if ( $replayErrorCount > 0 ) {
				$this->reportWorkerReplayErrors( $output, $worker, $replayErrorCount );
			}

			return;
		}

		if ( $status['signaled'] ) {
			$signal = $status['termsig'];
			$output->writeln(
				"<error>Worker {$worker} was killed by signal {$signal} "
				. '(' . $this->describeSignal( $signal ) . ').</error>'
			);
		} else {
			$output->writeln(
				"<error>Worker {$worker} failed with exit code {$status['exitcode']}.</error>"
			);
		}

		$tail = $this->workerStderrTail[$worker] ?? [];
		if ( $tail === [] ) {
			$output->writeln( "<comment>Worker {$worker} produced no error output.</comment>" );
		} else {
			$output->writeln( "<comment>Last error output of worker {$worker}:</comment>" );
			foreach ( $tail as $line ) {
				$output->writeln( "  {$line}" );
			}
		}

		if ( $replayErrorCount > 0 ) {
			$this->reportWorkerReplayErrors( $output, $worker, $replayErrorCount );
		}
	}

	/**
	 * Print the records of one worker that the parent could not write to storage.
	 */
	private function reportWorkerReplayErrors( OutputInterface $output, int $worker, int $count ): void {
		$output->writeln(
			"<error>Worker {$worker}: {$count} record(s) could not be written to storage.</error>"
		);
		foreach ( $this->workerReplayErrors[$worker] ?? [] as $message ) {
			$output->writeln( "  {$message}" );
		}
		if ( $count > count( $this->workerReplayErrors[$worker] ?? [] ) ) {
			$output->writeln( '  ... see the log table for the remaining records.' );
		}
	}

	/**
	 * Explain the signal that ended a worker in terms of the likely cause.
	 */
	private function describeSignal( int $signal ): string {
		$known = [
			2 => 'SIGINT - interrupted',
			6 => 'SIGABRT - aborted, e.g. a PHP fatal error',
			9 => 'SIGKILL - killed by the system, most likely out of memory',
			11 => 'SIGSEGV - segmentation fault in PHP or an extension',
			13 => 'SIGPIPE - the parent process closed the pipe before the worker was done',
			15 => 'SIGTERM - terminated',
		];

		return $known[$signal] ?? 'unknown signal';
	}

	/**
	 * Keep the trailing stderr lines of a worker so they can be shown if it dies.
	 */
	private function rememberWorkerStderr( int $worker, string $chunk ): void {
		foreach ( explode( "\n", rtrim( $chunk, "\n" ) ) as $line ) {
			$line = rtrim( $line, "\r" );
			if ( trim( $line ) === '' ) {
				continue;
			}
			$this->workerStderrTail[$worker][] = $line;
		}

		$overflow = count( $this->workerStderrTail[$worker] ) - self::STDERR_TAIL_LINES;
		if ( $overflow > 0 ) {
			$this->workerStderrTail[$worker] = array_slice( $this->workerStderrTail[$worker], $overflow );
		}
	}

	/**
	 * Drain complete newline-delimited worker records from the buffer.
	 *
	 * Worker DB traffic is sent as one JSON message per line, so this helper
	 * accumulates partial chunks until a full line is available and optionally
	 * flushes the final tail when the pipe closes.
	 */
	private function replayBufferedWorkerOutput(
		string &$buffer,
		IDataWriter $dataWriter,
		int $worker,
		bool $flushTail = false
	): void {
		while ( ( $newline = strpos( $buffer, "\n" ) ) !== false ) {
			$line = substr( $buffer, 0, $newline );
			$buffer = substr( $buffer, $newline + 1 );
			$this->replayWorkerLine( $dataWriter, $line, $worker );
		}

		if ( $flushTail && $buffer !== '' ) {
			$this->replayWorkerLine( $dataWriter, $buffer, $worker );
		}
	}

	/**
	 * Replay one worker payload line into the target writer.
	 *
	 * The line is expected to contain JSON encoded as `[methodName, ...args]`.
	 * Invalid payloads are logged back through the same writer so the parent can
	 * preserve the failure without aborting the rest of the worker streams.
	 */
	private function replayWorkerLine( IDataWriter $dataWriter, string $line, int $worker ): void {
		$line = trim( $line );
		if ( $line === '' ) {
			return;
		}

		$data = json_decode( $line, true );
		if ( !is_array( $data ) || $data === [] ) {
			$this->recordReplayFailure( $dataWriter, $worker, 'malformed payload', $line );

			return;
		}

		$method = array_shift( $data );
		if ( !is_string( $method ) || !method_exists( $dataWriter, $method ) ) {
			$this->recordReplayFailure( $dataWriter, $worker, 'unknown writer method', $line );

			return;
		}

		// A single bad record must not tear down the orchestrator: that would close the
		// pipes and kill every remaining worker with SIGPIPE instead of reporting it.
		try {
			$dataWriter->{$method}( ...$data );
		} catch ( Throwable $e ) {
			$this->recordReplayFailure(
				$dataWriter,
				$worker,
				$method . '() failed: ' . $e->getMessage(),
				$line
			);
		}
	}

	/**
	 * Remember a record the parent could not apply and log it for later inspection.
	 */
	private function recordReplayFailure(
		IDataWriter $dataWriter,
		int $worker,
		string $reason,
		string $line
	): void {
		$this->workerReplayErrorCount[$worker] = ( $this->workerReplayErrorCount[$worker] ?? 0 ) + 1;
		if ( count( $this->workerReplayErrors[$worker] ?? [] ) < self::REPLAY_ERROR_REPORT_LIMIT ) {
			$this->workerReplayErrors[$worker][] = $reason;
		}

		$this->addReplayLogEntry( $dataWriter, "[worker {$worker}] {$reason}: {$line}" );
	}

	/**
	 * Record malformed worker pipe output through the shared writer contract.
	 *
	 * The replay code treats invalid payloads as data, not exceptions, so the
	 * parent process can keep draining other worker streams and persist a useful
	 * error record for later inspection.
	 */
	protected function addReplayLogEntry( IDataWriter $dataWriter, string $line ): void {
		$dataWriter->addLogEntry( 'error', 'replay.invalid-worker-output', __CLASS__, $line );
	}

	/**
	 * Forward plain stdout/stderr chunks from a worker to the parent output.
	 *
	 * Output is prefixed with the worker index so interleaved logs remain
	 * readable when multiple children write at once.
	 */
	private function forwardWorkerOutput( OutputInterface $output, int $worker, string $kind, string $chunk ): void {
		$prefix = $kind === 'err' ? 'err' : 'out';
		foreach ( explode( "\n", rtrim( $chunk, "\n" ) ) as $line ) {
			$output->writeln( "[Worker {$worker} {$prefix}] " . $line );
		}
	}

	/**
	 * Terminate already-started child processes after a launch failure.
	 *
	 * This is a best-effort cleanup path used only when worker creation fails
	 * partway through startup, so the parent process does not leave orphaned
	 * children behind.
	 *
	 * @param array<int,resource> $procs already-started children to clean up
	 */
	private function terminateWorkers( array $procs ): void {
		foreach ( $procs as $proc ) {
			proc_terminate( $proc );
			proc_close( $proc );
		}
	}

	/**
	 * @return void
	 */
	abstract protected function logExecutionTime(): void;

	/**
	 * @return bool
	 */
	abstract protected function doProcessFile(): bool;

	/**
	 * Create the writer used by the parent process for direct storage access.
	 *
	 * The derived class owns the storage backend choice, because only it knows
	 * whether the command should write to a database, a workspace file, or some
	 * other project-specific target.
	 */
	abstract protected function getDataWriter(): IDataWriter;

	/**
	 * Create the writer used by child worker processes.
	 *
	 * Child writers usually send structured messages over the worker pipe so the
	 * parent process can replay them against the direct writer in a single place.
	 */
	abstract protected function getWorkerDataWriter(): IDataWriter;

	/**
	 * Create the reader used by the parent process.
	 *
	 * Commands that do not need read-side access can keep the default `null`
	 * implementation. Storage-backed commands override this when they need to
	 * inspect the same workspace or database that they write to.
	 */
	protected function getDataReader(): ?IDataReader {
		return null;
	}

	/**
	 * Create the reader used by child worker processes.
	 *
	 * The default is `null` because not every worker command has a separate
	 * read-side dependency, but DB-backed workers can override this to open the
	 * same source data in child processes.
	 */
	protected function getWorkerDataReader(): ?IDataReader {
		return null;
	}

}
