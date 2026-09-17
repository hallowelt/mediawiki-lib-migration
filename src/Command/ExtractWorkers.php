<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\CliWorkersCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IExtractor;
use HalloWelt\MediaWiki\Lib\Migration\IFileProcessorEventHandler;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;

/**
 * Worker-aware base command for extraction workflows.
 */
abstract class ExtractWorkers extends CliWorkersCommandBase {

	/**
	 * Holds initialized extractors indexed by configuration key.
	 *
	 * @var IExtractor[]
	 */
	protected $extractors = [];

	/**
	 * Returns the name of the command.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'extract';
	}

	/**
	 * Builds extractors from configuration and registers event-capable instances.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function beforeProcessFiles(): void {
		parent::beforeProcessFiles();
		$this->initExtractors();
	}

	/**
	 * Instantiates extractor services defined in the command configuration.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function initExtractors() {
		$this->extractors = [];
		$extractorFactoryCallbacks = $this->config['extractors'];
		foreach ( $extractorFactoryCallbacks as $key => $callback ) {
			$extractor = call_user_func_array(
				$callback,
				$this->getCallbackArguments()
			);
			if ( $extractor instanceof IExtractor === false ) {
				throw new Exception(
					"Factory callback for extractor '$key' did not return an "
					. "IExtractor object"
				);
			}
			if ( $extractor instanceof IOutputAwareInterface ) {
				$extractor->setOutput( $this->output );
			}
			$this->extractors[$key] = $extractor;
			if ( $extractor instanceof IFileProcessorEventHandler ) {
				$this->eventhandlers[$key] = $extractor;
			}
		}
	}

	/**
	 * Executes all prepared extractors for the currently processed file.
	 *
	 * @return bool
	 */
	protected function doProcessFile(): bool {
		foreach ( $this->extractors as $key => $extractor ) {
			$result = $extractor->extract( $this->currentFile );
			// TODO: Evaluate result
		}
		return true;
	}

	/**
	 * Returns constructor arguments passed to extractor factory callbacks.
	 *
	 * @return array
	 */
	protected function getCallbackArguments(): array {
		return [ $this->config, $this->workspace ];
	}

	/**
	 * Writes a human-readable command runtime to command output.
	 *
	 * @return void
	 */
	protected function logExecutionTime(): void {
		$time = $this->executionTime->getHumanReadableTime();
		$this->output->writeln( "\nExecution time: {$time}\n" );
	}
}
