<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\CliWorkersCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IComposer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;

/**
 * Worker-aware base command for compose workflows.
 */
abstract class ComposeWorkers extends CliWorkersCommandBase {

	/**
	 * Holds initialized composers indexed by configuration key.
	 *
	 * @var IComposer[]
	 */
	protected $composers = [];

	/**
	 * Returns the name of the command.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'compose';
	}

	/**
	 * Overrides file discovery because compose works from stored migration data.
	 *
	 * @return array
	 */
	protected function makeFileList() {
		return [];
	}

	/**
	 * Initializes composer instances once before file processing starts.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function beforeProcessFiles(): void {
		parent::beforeProcessFiles();
		$this->initComposers();
	}

	/**
	 * Initializes workspace context and builds the final XML export document.
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function processFiles(): int {
		$this->beforeProcessFiles();
		$this->ensureTargetDirs();
		$this->workspace = new Workspace( new SplFileInfo( $this->src ) );

		$mediawikixmlbuilder = new Builder();
		foreach ( $this->composers as $composer ) {
			$composer->buildXML( $mediawikixmlbuilder );
		}
		$mediawikixmlbuilder->buildAndSave( $this->dest . '/result/output.xml' );

		$this->logExecutionTime();

		return Command::SUCCESS;
	}

	/**
	 * Instantiates composer services defined in the command configuration.
	 *
	 * @return IComposer[]
	 * @throws Exception
	 */
	protected function initComposers() {
		$this->composers = [];
		$composerCallbacks = $this->config['composers'];
		foreach ( $composerCallbacks as $key => $callback ) {
			$composer = call_user_func_array(
				$callback,
				$this->getCallbackArguments()
			);
			if ( $composer instanceof IComposer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IComposer object"
				);
			}
			if ( $composer instanceof IOutputAwareInterface ) {
				$composer->setOutput( $this->output );
			}
			$this->composers[$key] = $composer;
		}
	}

	/**
	 * Satisfies the base processing contract for commands without file iteration.
	 *
	 * @return bool
	 */
	protected function doProcessFile(): bool {
		// Do nothing
		return true;
	}

	/**
	 * Ensures the compose output directory exists before files are written.
	 *
	 * @return void
	 */
	private function ensureTargetDirs() {
		$path = "{$this->dest}/result/images";
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
	}

	/**
	 * Returns constructor arguments passed to composer factory callbacks.
	 *
	 * @return array
	 */
	protected function getCallbackArguments(): array {
		return [ $this->config ];
	}

	/**
	 * Writes a human-readable command runtime to command output.
	 *
	 * @return void
	 */
	protected function logExecutionTime(): void {
		$time = $this->executionTime->getHumanReadableTime();
		$this->output->writeln( "\nExecution time: {$time}\n" );
	}
}
