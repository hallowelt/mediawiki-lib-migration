<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\CommandLineTools\Commands\BatchFileProcessorBase;
use SplFileInfo;

abstract class CliCommandBase extends BatchFileProcessorBase {

	/**
	 *
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
	 *
	 * @var IFileProcessorEventHandler
	 */
	protected $eventhandlers = [];

	/**
	 *
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
		$workspaceDir = new SplFileInfo( $this->dest );
		$this->workspace = new Workspace( $workspaceDir );
		$this->buckets = new DataBuckets( $this->getBucketKeys() );
		$this->buckets->loadFromWorkspace( $this->workspace );
	}

	protected function afterProcessFiles() {
		$this->buckets->saveToWorkspace( $this->workspace );
	}

	/**
	 *
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
	 *
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
	 *
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if ( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
	}
}
