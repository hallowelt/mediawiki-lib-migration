<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;
use HalloWelt\MediaWiki\Lib\Migration\Logging\LogEntryWriter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
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
	 * Strip the default console handler so a test can assert on just the handlers it added
	 * (e.g. a db writer) without noise from actually writing to the real console.
	 *
	 * @return void
	 */
	private function stripConsoleHandler(): void {
		$logger = Log::getLogger();
		$ref = new \ReflectionClass( Log::class );
		$prop = $ref->getProperty( 'handlers' );
		$handlers = $prop->getValue();
		unset( $handlers['console'] );
		$prop->setValue( null, $handlers );
		$logger->setHandlers( array_values( $handlers ) );
	}

	/**
	 * Register an extra handler directly, bypassing the public API, to verify how Log
	 * routes/processes records once a given handler is present (there is no public API for
	 * adding arbitrary handlers like a file sink; downstream projects only get console/db).
	 *
	 * @param string $name
	 * @param HandlerInterface $handler
	 * @return void
	 */
	private function addHandler( string $name, HandlerInterface $handler ): void {
		$logger = Log::getLogger();
		$ref = new \ReflectionClass( Log::class );
		$prop = $ref->getProperty( 'handlers' );
		$handlers = $prop->getValue();
		$handlers[$name] = $handler;
		$prop->setValue( null, $handlers );
		$logger->pushHandler( $handler );
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

		$this->stripConsoleHandler();
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
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setDatabaseHandler
	 * @return void
	 */
	public function testSetDatabaseHandlerReplacesAndRemovesPreviousHandler(): void {
		$makeWriter = static function () {
			return new class implements LogEntryWriter {
				/** @var int */
				public $count = 0;

				public function addLogEntry(
					string $timestamp,
					string $type,
					string $step,
					string $caller,
					string $text
				): void {
					$this->count++;
				}
			};
		};
		$first = $makeWriter();
		$second = $makeWriter();

		$this->stripConsoleHandler();
		Log::setDatabaseHandler( $first );
		Log::setDatabaseHandler( $second );
		Log::info( 'one' );
		Log::to( 'db' )->info( 'two' );

		$this->assertSame( 0, $first->count );
		$this->assertSame( 2, $second->count );

		Log::setDatabaseHandler( null );
		Log::info( 'three' );
		Log::to( 'db' )->info( 'four' );

		$this->assertSame( 2, $second->count );
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

		$this->stripConsoleHandler();
		$this->addHandler( 'file', new StreamHandler( $this->logFile, Level::Debug ) );
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
		$this->stripConsoleHandler();
		$this->addHandler( 'file', new StreamHandler( $this->logFile, Level::Debug ) );

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

		$this->stripConsoleHandler();
		$this->addHandler( 'file', new StreamHandler( $this->logFile, Level::Debug ) );
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
		$output = new \Symfony\Component\Console\Output\BufferedOutput();
		Log::setConsoleOutput( $output, 'notice' );

		Log::warning( 'routed message' );

		$this->assertStringContainsString( 'routed message', $output->fetch() );
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\Logging\Log::setConsoleOutput
	 * @return void
	 */
	public function testSetConsoleOutputPreservesConfiguredLevelByDefault(): void {
		$initialOutput = new \Symfony\Component\Console\Output\BufferedOutput();
		Log::setConsoleOutput( $initialOutput, 'error' );

		$output = new \Symfony\Component\Console\Output\BufferedOutput();
		Log::setConsoleOutput( $output );

		Log::notice( 'should stay quiet' );
		Log::error( 'should appear' );

		$fetched = $output->fetch();
		$this->assertStringNotContainsString( 'should stay quiet', $fetched );
		$this->assertStringContainsString( 'should appear', $fetched );
	}
}
