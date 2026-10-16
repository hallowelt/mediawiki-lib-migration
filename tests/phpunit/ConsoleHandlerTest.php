<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\Logging\ConsoleHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHandlerTest extends TestCase {

	/**
	 * @param OutputInterface $output
	 * @return Logger
	 */
	private function makeLogger( OutputInterface $output ): Logger {
		$logger = new Logger( 'test' );
		$logger->pushHandler( new ConsoleHandler( $output, Level::Debug ) );
		return $logger;
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\ConsoleHandler
	 * @return void
	 */
	public function testQuietVerbosityHidesWarningsAndErrors(): void {
		$output = new BufferedOutput();
		$output->setVerbosity( OutputInterface::VERBOSITY_QUIET );
		$logger = $this->makeLogger( $output );

		$logger->warning( 'hidden' );
		$logger->error( 'hidden too' );

		$this->assertSame( '', $output->fetch() );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\ConsoleHandler
	 * @return void
	 */
	public function testNoticeAndInfoNeedIncreasedVerbosity(): void {
		$output = new BufferedOutput();
		$output->setVerbosity( OutputInterface::VERBOSITY_NORMAL );
		$logger = $this->makeLogger( $output );

		$logger->warning( 'visible by default' );
		$logger->notice( 'needs v' );
		$logger->info( 'needs vv' );

		$fetched = $output->fetch();
		$this->assertStringContainsString( 'visible by default', $fetched );
		$this->assertStringNotContainsString( 'needs', $fetched );

		$output->setVerbosity( OutputInterface::VERBOSITY_VERBOSE );
		$logger->notice( 'needs v' );
		$logger->info( 'needs vv' );
		$fetched = $output->fetch();
		$this->assertStringContainsString( 'needs v', $fetched );
		$this->assertStringNotContainsString( 'needs vv', $fetched );

		$output->setVerbosity( OutputInterface::VERBOSITY_VERY_VERBOSE );
		$logger->info( 'needs vv' );
		$this->assertStringContainsString( 'needs vv', $output->fetch() );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\ConsoleHandler
	 * @return void
	 */
	public function testSilentVerbositySuppressesEvenErrors(): void {
		$output = new BufferedOutput();
		$output->setVerbosity( OutputInterface::VERBOSITY_SILENT );
		$logger = $this->makeLogger( $output );

		$logger->emergency( 'nothing should show' );

		$this->assertSame( '', $output->fetch() );
	}
}
