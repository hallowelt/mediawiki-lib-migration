<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\CommandLineTools\Commands\BatchFileProcessorBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use SplFileInfo;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MediaWiki\Lib\Migration\IFileProcessorEventHandler;


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

	public function __construct( $config ) {
		parent::__construct();
		$this->config = $config;
	}

	protected function processFiles() {
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

	protected function getBucketKeys() {
		return [];
	}

	protected function runBeforeProcessFilesEventHandlers() {
		foreach( $this->eventhandlers as $handler ) {
			$handler->beforeProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	protected function runAfterProcessFilesEventHandlers() {
		foreach( $this->eventhandlers as $handler ) {
			$handler->afterProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return boolean
	 */
	protected function processFile( SplFileInfo $file ): bool {
		//TODO: Ensure workspace dirs!?
		return $this->doProcessFile();
	}

	/**
	 * @return boolean
	 */
	protected abstract function doProcessFile(): bool;

	/**
	 *
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
	}
}