<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Static facade around a single Monolog logger shared by all downstream projects, so that
 * calling code can just do `Log::info( 'message' )` instead of threading a logger instance
 * through constructors / `getLogger()` calls.
 */
class Log {

	/** @var Logger|null */
	private static $logger = null;

	/** @var string */
	private static $step = '';

	/** @var array<string,HandlerInterface> */
	private static $handlers = [];

	/**
	 * Route the console handler through an already-existing Symfony `OutputInterface`
	 * (e.g. the one a `Command::execute( $input, $output )` receives) instead of the default
	 * handler's own `new ConsoleOutput()`. Call this early (e.g. in a Command's constructor/
	 * `beforeProcessFiles()`) so `Log::*()` calls share the same stream, verbosity and TTY/color
	 * detection as code still writing directly via `$output->writeln()` (IOutputAwareInterface).
	 *
	 * @param OutputInterface $output
	 * @param int|string|null $level Keep the current console handler's level if null
	 * @return void
	 */
	public static function setConsoleOutput( OutputInterface $output, $level = null ): void {
		$logger = self::getLogger();
		if ( $level === null ) {
			$level = isset( self::$handlers['console'] )
				? self::$handlers['console']->getLevel()
				: 'notice';
		}
		self::$handlers['console'] = new ConsoleHandler( $output, $level );
		$logger->setHandlers( array_values( self::$handlers ) );
	}

	/**
	 * Inject the DB log sink. The handler instance (e.g. backed by a `WorkspaceDB`) is
	 * created by the downstream project and should be wired up early, before logging starts.
	 *
	 * Calling it again replaces the previous DB handler; passing null removes it.
	 *
	 * @param LogEntryWriter|null $writer
	 * @param int|string $level
	 * @return void
	 */
	public static function setDatabaseHandler( ?LogEntryWriter $writer, $level = 'debug' ): void {
		$logger = self::getLogger();
		unset( self::$handlers['db'] );
		$logger->setHandlers( array_values( self::$handlers ) );
		if ( $writer === null ) {
			return;
		}
		$handler = new DatabaseHandler( $writer, $level );
		self::$handlers['db'] = $handler;
		$logger->pushHandler( $handler );
	}

	/**
	 * Set the current migration step (analyze/extract/convert/compose). Used as the `step`
	 * column for the DB handler; auto-filled into every subsequent log record's context
	 * unless a record already carries its own `step`.
	 *
	 * @param string $step
	 * @return void
	 */
	public static function setStep( string $step ): void {
		self::$step = $step;
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function emergency( string $message, array $context = [] ): void {
		self::getLogger()->emergency( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function alert( string $message, array $context = [] ): void {
		self::getLogger()->alert( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function critical( string $message, array $context = [] ): void {
		self::getLogger()->critical( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::getLogger()->error( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::getLogger()->warning( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function notice( string $message, array $context = [] ): void {
		self::getLogger()->notice( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::getLogger()->info( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		self::getLogger()->debug( $message, $context );
	}

	/**
	 * Log a Throwable with its message and full stack trace. The exception is passed as
	 * `context['exception']` (PSR-3 convention): the file handler's formatter expands it with
	 * class/message/trace, and DatabaseHandler appends the full trace to its `text` column too,
	 * so no target silently drops it even though only the file target does structured
	 * expansion by default.
	 *
	 * @param Throwable $exception
	 * @param array $context
	 * @param string|int $level
	 * @return void
	 */
	public static function logException( Throwable $exception, array $context = [], $level = 'error' ): void {
		$context['exception'] = $exception;
		$message = $exception->getMessage() !== '' ? $exception->getMessage() : get_class( $exception );
		self::getLogger()->log( $level, $message, $context );
	}

	/**
	 * Address a subset of the configured handlers by name for one call, e.g.
	 * `Log::to( 'db' )->debug( ... )` or `Log::to( 'db', 'console' )->error( ... )`.
	 * Handler names are the built-in `console` (default handler) and `db`
	 * (see setDatabaseHandler()).
	 *
	 * @param string ...$names
	 * @return Logger
	 */
	public static function to( string ...$names ): Logger {
		$logger = self::getLogger();
		$selected = array_values( array_intersect_key( self::$handlers, array_flip( $names ) ) );
		$scoped = new Logger( 'migration', $selected );
		foreach ( $logger->getProcessors() as $processor ) {
			$scoped->pushProcessor( $processor );
		}
		return $scoped;
	}

	/**
	 * The shared PSR-3 logger with all configured handlers, e.g. for handing to a
	 * `LoggerAwareInterface` object.
	 *
	 * @return Logger
	 */
	public static function getLogger(): Logger {
		if ( self::$logger === null ) {
			self::$logger = new Logger( 'migration' );
			// Verbosity (-v/-vv/-vvv) is filtered by ConsoleLogger, not by the handler level
			self::$handlers['console'] = new ConsoleHandler( new ConsoleOutput(), Level::Debug );
			self::$logger->pushHandler( self::$handlers['console'] );
			self::$logger->pushProcessor(
				new IntrospectionProcessor( Level::Debug, [ self::class ] )
			);
			self::$logger->pushProcessor( new PsrLogMessageProcessor() );
			self::$logger->pushProcessor( static function ( $record ) {
				if ( self::$step !== '' && !isset( $record->context['step'] ) ) {
					$record = $record->with( context: [ 'step' => self::$step ] + $record->context );
				}
				return $record;
			} );
		}
		return self::$logger;
	}
}
