<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Logging;

use Monolog\Handler\PsrHandler;
use Monolog\Level;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Logs to STDOUT/STDERR via symfony/console's ConsoleLogger: colors iff the underlying
 * stream is a tty (the Output's own default detection), errors go to STDERR automatically.
 *
 * Verbosity follows ConsoleLogger's defaults: warning and above are shown by default,
 * notice with `-v`, info with `-vv`, debug with `-vvv`; `--quiet` hides all of them.
 */
class ConsoleHandler extends PsrHandler {

	/**
	 * @param OutputInterface $output
	 * @param int|string|Level $level
	 * @param bool $bubble
	 */
	public function __construct( OutputInterface $output, $level = Level::Debug, bool $bubble = true ) {
		$consoleLogger = new ConsoleLogger( $output );
		parent::__construct( $consoleLogger, $level, $bubble );
	}
}
