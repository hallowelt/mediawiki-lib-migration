<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\CliWorkersCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;

/**
 * Base analyze command variant that supports worker orchestration.
 */
abstract class AnalyzeWorkers extends CliWorkersCommandBase {

	/**
	 * Holds initialized analyzers indexed by configuration key.
	 *
	 * @var IAnalyzer[]
	 */
	protected $analyzers = [];

	/**
	 * Returns the name of the command.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'analyze';
	}

	/**
	 * Initializes analyzer instances once before file processing starts.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function beforeProcessFiles(): void {
		parent::beforeProcessFiles();
		$this->initAnalyzers();
	}

	/**
	 * Instantiates analyzer services defined in the command configuration.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function initAnalyzers() {
		$this->analyzers = [];
		$analyzerFactoryCallbacks = $this->config['analyzers'];
		foreach ( $analyzerFactoryCallbacks as $key => $callback ) {
			$analyzer = call_user_func_array(
				$callback,
				$this->getCallbackArguments()
			);
			if ( $analyzer instanceof IAnalyzer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IAnalyzer object"
				);
			}
			if ( $analyzer instanceof IOutputAwareInterface ) {
				$analyzer->setOutput( $this->output );
			}
			$this->analyzers[$key] = $analyzer;
		}
	}

	/**
	 * Builds analyzers from configured factories and runs each on the current file.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function doProcessFile(): bool {
		$this->output->writeln( "Analyzing file '{$this->currentFile->getFilename()}'" );
		foreach ( $this->analyzers as $analyzer ) {
			$result = $analyzer->analyze( $this->currentFile );
		}
		return true;
	}

	/**
	 * Returns constructor arguments passed to analyzer factory callbacks.
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
