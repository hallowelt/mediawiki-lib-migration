<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\CommandLineTools\Commands\BatchFileProcessorBase;
use HalloWelt\MediaWiki\Lib\Migration\Logging\Log;
use SplFileInfo;

abstract class CliCommandBase extends BatchFileProcessorBase {

	/** @var ExecutionTime */
	protected $executionTime;

	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * @var Workspace
	 */
	protected $workspace = null;

	/**
	 * @var DataBuckets
	 */
	protected $buckets = null;

	/**
	 * @var DataBuckets
	 */
	protected $executionTimeBuckets = null;

	/**
	 * @var IFileProcessorEventHandler
	 */
	protected $eventhandlers = [];

	/**
	 * @param array $config
	 */
	public function __construct( $config ) {
		parent::__construct();
		$this->config = $config;
	}

	protected function processFiles(): int {
		$this->beforeProcessFiles();
		$this->runBeforeProcessFilesEventHandlers();
		$returnValue = parent::processFiles();
		$this->runAfterProcessFilesEventHandlers();
		$this->afterProcessFiles();
		return $returnValue;
	}

	protected function beforeProcessFiles() {
		// Route Log::*() through the same Output the command already writes to directly
		// (IOutputAwareInterface), so both share TTY/color detection and verbosity.
		Log::setConsoleOutput( $this->output );
		// Tags every subsequent log record (e.g. the DB handler's `step` column) with the
		// command name (analyze/extract/convert/compose), set via setName() in configure().
		Log::setStep( $this->getName() ?? '' );

		if ( !is_dir( $this->dest ) ) {
			Log::error( "Destination does not exist" );
			exit();
		}
		$workspaceDir = new SplFileInfo( $this->dest );
		$this->workspace = new Workspace( $workspaceDir );

		$this->initExecutionTime();

		$this->buckets = new DataBuckets( $this->getBucketKeys() );
		$this->buckets->loadFromWorkspace( $this->workspace );
	}

	protected function afterProcessFiles() {
		$this->buckets->saveToWorkspace( $this->workspace );
		$this->logExecutionTime();
	}

	protected function initExecutionTime() {
		$this->executionTime = new ExecutionTime();
		$this->executionTimeBuckets = new DataBuckets( [ 'execution-time' ] );
		$this->executionTimeBuckets->loadFromWorkspace( $this->workspace );
	}

	protected function logExecutionTime() {
		$time = $this->executionTime->getHumanReadableTime();
		Log::notice( "\nExecution time: {$time}\n" );
		$this->executionTimeBuckets->addData(
			'execution-time',
			$this->getName(),
			$time,
			false,
			true
		);
		$this->executionTimeBuckets->saveToWorkspace( $this->workspace );
	}

	/**
	 * @return array
	 */
	protected function getBucketKeys() {
		return [];
	}

	protected function runBeforeProcessFilesEventHandlers() {
		foreach ( $this->eventhandlers as $handler ) {
			$handler->beforeProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	protected function runAfterProcessFilesEventHandlers() {
		foreach ( $this->eventhandlers as $handler ) {
			$handler->afterProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected function processFile( SplFileInfo $file ): bool {
		// TODO: Ensure workspace dirs!?
		return $this->doProcessFile();
	}

	/**
	 * @return bool
	 */
	abstract protected function doProcessFile(): bool;

	/**
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if ( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
	}
}
