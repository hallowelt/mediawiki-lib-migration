<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
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
 *
 * Configuration is inspired by Django's `LOGGING` dict: a set of named `formatters` and a
 * set of named `handlers`, each with its own level (verbosity) and formatter. All handlers
 * receive the same log record; per-handler `level` decides who stays quiet and who doesn't.
 *
 * @see https://docs.djangoproject.com/en/6.0/ref/logging/#default-logging-configuration
 */
class Log {

	/** @var Logger|null */
	private static $logger = null;

	/** @var string */
	private static $step = '';

	/** @var array<string,HandlerInterface> */
	private static $handlers = [];

	/**
	 * Apply a global handler/formatter configuration. Replaces any previously configured
	 * handlers (a database handler injected via setDatabaseHandler() is kept, since it is
	 * meant to be set up early and independently, see setDatabaseHandler()).
	 *
	 * Example:
	 * <code>
	 * Log::configure( [
	 *     'formatters' => [
	 *         'default' => [ 'format' => "%datetime% %channel%.%level_name%: %message%\n" ],
	 *     ],
	 *     'handlers' => [
	 *         'console' => [ 'level' => 'notice' ],
	 *         'file' => [ 'level' => 'debug', 'path' => '/path/to/migration.log', 'formatter' => 'default' ],
	 *     ],
	 * ] );
	 * </code>
	 *
	 * A handler entry may also directly provide a ready-made handler via the `handler` key,
	 * for target types beyond the built-in `console`/file (`path`) ones.
	 *
	 * @param array $config
	 * @return void
	 */
	public static function configure( array $config ): void {
		$logger = self::getLogger();
		$dbHandler = self::$handlers['db'] ?? null;

		foreach ( $logger->getHandlers() as $handler ) {
			$logger->popHandler();
		}
		unset( $handler );
		self::$handlers = $dbHandler !== null ? [ 'db' => $dbHandler ] : [];

		$formatters = self::buildFormatters( $config['formatters'] ?? [] );
		foreach ( $config['handlers'] ?? [] as $name => $handlerConfig ) {
			$handler = self::buildHandler( $name, $handlerConfig, $formatters );
			self::$handlers[$name] = $handler;
			$logger->pushHandler( $handler );
		}

		if ( $dbHandler !== null ) {
			$logger->pushHandler( $dbHandler );
		}
	}

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
	 * Inject the DB log sink. Kept separate from configure() because the handler instance
	 * (e.g. a `WorkspaceDB`) is created by the downstream project, not from a config array,
	 * and should be wired up early, before/independently of the rest of the configuration.
	 *
	 * @param LogEntryWriter $writer
	 * @param int|string $level
	 * @return void
	 */
	public static function setDatabaseHandler( LogEntryWriter $writer, $level = 'debug' ): void {
		$handler = new DatabaseHandler( $writer, $level );
		self::$handlers['db'] = $handler;
		self::getLogger()->pushHandler( $handler );
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
	 * `Log::to( 'file' )->debug( ... )` or `Log::to( 'db', 'console' )->error( ... )`.
	 * Handler names are the keys used in configure()'s `handlers` array, plus the built-in
	 * `console` (default handler) and `db` (see setDatabaseHandler()).
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
	 * @return Logger
	 */
	private static function getLogger(): Logger {
		if ( self::$logger === null ) {
			self::$logger = new Logger( 'migration' );
			self::$handlers['console'] = new ConsoleHandler( new ConsoleOutput(), 'notice' );
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

	/**
	 * @param array $config
	 * @return array<string,LineFormatter>
	 */
	private static function buildFormatters( array $config ): array {
		$formatters = [];
		foreach ( $config as $name => $formatterConfig ) {
			$formatters[$name] = new LineFormatter(
				$formatterConfig['format'] ?? null,
				$formatterConfig['date_format'] ?? null
			);
		}
		return $formatters;
	}

	/**
	 * @param string $name
	 * @param array $handlerConfig
	 * @param array<string,LineFormatter> $formatters
	 * @return HandlerInterface
	 */
	private static function buildHandler( string $name, array $handlerConfig, array $formatters ): HandlerInterface {
		if ( isset( $handlerConfig['handler'] ) && $handlerConfig['handler'] instanceof HandlerInterface ) {
			$handler = $handlerConfig['handler'];
		} elseif ( isset( $handlerConfig['path'] ) ) {
			$handler = new StreamHandler( $handlerConfig['path'], $handlerConfig['level'] ?? 'debug' );
		} else {
			$handler = new ConsoleHandler(
				$handlerConfig['output'] ?? new ConsoleOutput(),
				$handlerConfig['level'] ?? 'debug'
			);
		}

		if ( isset( $handlerConfig['formatter'] ) && isset( $formatters[$handlerConfig['formatter']] ) ) {
			$handler->setFormatter( $formatters[$handlerConfig['formatter']] );
		}

		return $handler;
	}
}
