<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;
use HalloWelt\MediaWiki\Lib\Migration\Logging\LogEntryWriter;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase {

	/** @var string */
	private $logFile;

	protected function setUp(): void {
		parent::setUp();
		$this->logFile = sys_get_temp_dir() . '/LogTest_' . uniqid() . '.log';
		$this->resetLogSingleton();
	}

	protected function tearDown(): void {
		if ( file_exists( $this->logFile ) ) {
			unlink( $this->logFile );
		}
		$this->resetLogSingleton();
		parent::tearDown();
	}

	/**
	 * Reset the static Logger singleton so tests don't leak handlers/step into each other.
	 *
	 * @return void
	 */
	private function resetLogSingleton(): void {
		$ref = new \ReflectionClass( Log::class );
		$ref->getProperty( 'logger' )->setValue( null, null );
		$ref->getProperty( 'step' )->setValue( null, '' );
		$ref->getProperty( 'handlers' )->setValue( null, [] );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::configure
	 * @return void
	 */
	public function testConfigureWritesToFileWithConfiguredLevel(): void {
		Log::configure( [
			'handlers' => [
				'file' => [ 'level' => 'warning', 'path' => $this->logFile ],
			],
		] );

		Log::info( 'should not appear' );
		Log::warning( 'should appear' );

		$content = file_get_contents( $this->logFile );
		$this->assertStringNotContainsString( 'should not appear', $content );
		$this->assertStringContainsString( 'should appear', $content );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setDatabaseHandler
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setStep
	 * @return void
	 */
	public function testDatabaseHandlerReceivesTypeStepCallerAndText(): void {
		$writer = new class implements LogEntryWriter {
			/** @var array */
			public $calls = [];

			public function addLogEntry(
				string $timestamp,
				string $type,
				string $step,
				string $caller,
				string $text
			): void {
				$this->calls[] = [ $timestamp, $type, $step, $caller, $text ];
			}
		};

		Log::configure( [ 'handlers' => [] ] );
		Log::setStep( 'extract' );
		Log::setDatabaseHandler( $writer );

		Log::error( 'something failed' );

		$this->assertCount( 1, $writer->calls );
		[ $timestamp, $type, $step, $caller, $text ] = $writer->calls[0];
		$this->assertNotFalse( \DateTimeImmutable::createFromFormat( DATE_ATOM, $timestamp ) );
		$this->assertSame( 'ERROR', $type );
		$this->assertSame( 'extract', $step );
		$this->assertStringContainsString( __CLASS__, $caller );
		$this->assertSame( 'something failed', $text );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::configure
	 * @return void
	 */
	public function testConfigureKeepsDatabaseHandlerAcrossReconfigure(): void {
		$writer = new class implements LogEntryWriter {
			/** @var array */
			public $calls = [];

			public function addLogEntry(
				string $timestamp,
				string $type,
				string $step,
				string $caller,
				string $text
			): void {
				$this->calls[] = [ $timestamp, $type, $step, $caller, $text ];
			}
		};

		Log::setDatabaseHandler( $writer );
		Log::configure( [
			'handlers' => [
				'file' => [ 'level' => 'debug', 'path' => $this->logFile ],
			],
		] );

		Log::info( 'hello' );

		$this->assertCount( 1, $writer->calls );
		$this->assertStringContainsString( 'hello', file_get_contents( $this->logFile ) );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::info
	 * @return void
	 */
	public function testDefaultLoggerDoesNotThrowWithoutConfiguration(): void {
		Log::info( 'no explicit configuration needed' );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::to
	 * @return void
	 */
	public function testToSendsMessageJustToSelectedHandlers(): void {
		$writer = new class implements LogEntryWriter {
			/** @var array */
			public $calls = [];

			public function addLogEntry(
				string $timestamp,
				string $type,
				string $step,
				string $caller,
				string $text
			): void {
				$this->calls[] = [ $timestamp, $type, $step, $caller, $text ];
			}
		};

		Log::configure( [
			'handlers' => [
				'file' => [ 'level' => 'debug', 'path' => $this->logFile ],
			],
		] );
		Log::setDatabaseHandler( $writer );

		Log::to( 'db' )->error( 'db-only message' );

		$this->assertCount( 1, $writer->calls );
		$this->assertSame( '', file_exists( $this->logFile ) ? file_get_contents( $this->logFile ) : '' );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::info
	 * @return void
	 */
	public function testPsr3PlaceholdersAreInterpolatedIntoMessage(): void {
		Log::configure( [
			'handlers' => [
				'file' => [ 'level' => 'debug', 'path' => $this->logFile ],
			],
		] );

		Log::info( 'Processing {path} for user {user}', [ 'path' => '/a/b.txt', 'user' => 'jdoe' ] );

		$content = file_get_contents( $this->logFile );
		$this->assertStringContainsString( 'Processing /a/b.txt for user jdoe', $content );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::logException
	 * @return void
	 */
	public function testLogExceptionIncludesMessageAndTraceOnAllTargets(): void {
		$writer = new class implements LogEntryWriter {
			/** @var array */
			public $calls = [];

			public function addLogEntry(
				string $timestamp,
				string $type,
				string $step,
				string $caller,
				string $text
			): void {
				$this->calls[] = [ $timestamp, $type, $step, $caller, $text ];
			}
		};

		Log::configure( [
			'handlers' => [
				'file' => [ 'level' => 'debug', 'path' => $this->logFile ],
			],
		] );
		Log::setDatabaseHandler( $writer );

		$exception = new \RuntimeException( 'boom' );
		Log::logException( $exception );

		$this->assertCount( 1, $writer->calls );
		[ , $type, , , $text ] = $writer->calls[0];
		$this->assertSame( 'ERROR', $type );
		$this->assertStringContainsString( 'boom', $text );
		$this->assertStringContainsString( 'RuntimeException', $text );

		$fileContent = file_get_contents( $this->logFile );
		$this->assertStringContainsString( 'boom', $fileContent );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setConsoleOutput
	 * @return void
	 */
	public function testSetConsoleOutputRoutesConsoleHandlerThroughGivenOutput(): void {
		Log::configure( [ 'handlers' => [ 'console' => [ 'level' => 'notice' ] ] ] );

		$output = new \Symfony\Component\Console\Output\BufferedOutput();
		Log::setConsoleOutput( $output );

		Log::notice( 'routed message' );

		$this->assertStringContainsString( 'routed message', $output->fetch() );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setConsoleOutput
	 * @return void
	 */
	public function testSetConsoleOutputPreservesConfiguredLevelByDefault(): void {
		Log::configure( [ 'handlers' => [ 'console' => [ 'level' => 'error' ] ] ] );

		$output = new \Symfony\Component\Console\Output\BufferedOutput();
		Log::setConsoleOutput( $output );

		Log::notice( 'should stay quiet' );
		Log::error( 'should appear' );

		$fetched = $output->fetch();
		$this->assertStringNotContainsString( 'should stay quiet', $fetched );
		$this->assertStringContainsString( 'should appear', $fetched );
	}
}
