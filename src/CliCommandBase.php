<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\CommandLineTools\Commands\BatchFileProcessorBase;
use SplFileInfo;

abstract class CliCommandBase extends BatchFileProcessorBase {

	/** @var ExecutionTime */
	protected $executionTime;

	/** @var IFileProcessorEventHandler */
	protected $eventhandlers = [];

	/**
	 * @var Workspace
	 */
	protected $workspace = null;

	/**
	 * @return int
	 */
	protected function processFiles(): int {
		$this->beforeProcessFiles();
		$this->runBeforeProcessFilesEventHandlers();
		$returnValue = parent::processFiles();
		$this->runAfterProcessFilesEventHandlers();
		$this->afterProcessFiles();
		return $returnValue;
	}

	/**
	 * @return void
	 */
	protected function beforeProcessFiles(): void {
		if ( !is_dir( $this->dest ) ) {
			$this->output->writeln( "Destination does not exist" );
			exit();
		}

		$workspaceDir = new SplFileInfo( $this->dest );
		$this->workspace = new Workspace( $workspaceDir );

		$this->initExecutionTime();
	}

	/**
	 * @return void
	 */
	protected function afterProcessFiles(): void {
		$this->logExecutionTime();
	}

	/**
	 * @return void
	 */
	protected function initExecutionTime(): void {
		$this->executionTime = new ExecutionTime();
	}

	/**
	 * @return string
	 */
	protected function getExecutionTime(): string {
		return $this->executionTime->getHumanReadableTime();
	}

	/**
	 * @return void
	 */
	protected function runBeforeProcessFilesEventHandlers() {
		foreach ( $this->eventhandlers as $handler ) {
			$handler->beforeProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	/**
	 * @return void
	 */
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
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if ( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
	}

	/**
	 * @return void
	 */
	abstract protected function logExecutionTime(): void;

	/**
	 * @return bool
	 */
	abstract protected function doProcessFile(): bool;
}
