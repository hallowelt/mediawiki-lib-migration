<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

use Monolog\Handler\PsrHandler;
use Monolog\Level;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Logs to STDOUT/STDERR via symfony/console's ConsoleLogger: colors iff the underlying
 * stream is a tty (the Output's own default detection), errors go to STDERR automatically.
 *
 * The default verbosity mapping is adjusted in two ways, deliberately departing from
 * ConsoleLogger's own defaults:
 * - NOTICE always shows at normal verbosity (like WARNING/ERROR): code logging at 'notice'
 *   (e.g. migrated from a plain `$output->writeln()`) relies on messages being visible by
 *   default, not gated behind `-v`.
 * - ERROR/CRITICAL/ALERT/EMERGENCY only require VERBOSITY_QUIET, so they stay visible even
 *   when the command is run with `--quiet`; a migration failure should not be silenced by
 *   the same flag that quiets down progress/informational noise. Use Symfony's `--silent`
 *   (if supported by the app) to suppress console output entirely, including errors.
 */
class ConsoleHandler extends PsrHandler {

	/**
	 * @param OutputInterface $output
	 * @param int|string|Level $level
	 * @param bool $bubble
	 */
	public function __construct( OutputInterface $output, $level = Level::Debug, bool $bubble = true ) {
		$consoleLogger = new ConsoleLogger( $output, [
			LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::ERROR => OutputInterface::VERBOSITY_QUIET,
			LogLevel::CRITICAL => OutputInterface::VERBOSITY_QUIET,
			LogLevel::ALERT => OutputInterface::VERBOSITY_QUIET,
			LogLevel::EMERGENCY => OutputInterface::VERBOSITY_QUIET,
		] );
		parent::__construct( $consoleLogger, $level, $bubble );
	}
}
