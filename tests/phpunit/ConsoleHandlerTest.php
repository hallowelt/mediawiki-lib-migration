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
	public function testErrorLevelsStayVisibleUnderQuietVerbosity(): void {
		$output = new BufferedOutput();
		$output->setVerbosity( OutputInterface::VERBOSITY_QUIET );
		$logger = $this->makeLogger( $output );

		$logger->notice( 'quiet please' );
		$logger->warning( 'quiet please too' );
		$logger->error( 'still shown' );
		$logger->critical( 'still shown too' );

		$fetched = $output->fetch();
		$this->assertStringNotContainsString( 'quiet please', $fetched );
		$this->assertStringContainsString( 'still shown', $fetched );
		$this->assertStringContainsString( 'still shown too', $fetched );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\ConsoleHandler
	 * @return void
	 */
	public function testNoticeStaysVisibleAtNormalVerbosity(): void {
		$output = new BufferedOutput();
		$output->setVerbosity( OutputInterface::VERBOSITY_NORMAL );
		$logger = $this->makeLogger( $output );

		$logger->notice( 'visible by default' );
		$logger->info( 'hidden by default' );

		$fetched = $output->fetch();
		$this->assertStringContainsString( 'visible by default', $fetched );
		$this->assertStringNotContainsString( 'hidden by default', $fetched );
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
