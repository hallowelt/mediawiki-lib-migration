<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\CliWorkersCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IConverter;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;

/**
 * Worker-aware base command for file conversion workflows.
 */
abstract class ConvertWorkers extends CliWorkersCommandBase {

	/**
	 * Stores the base output path used for converted files.
	 *
	 * @var string
	 */
	private $targetBasePath = '';

	/**
	 * Holds initialized converters indexed by configuration key.
	 *
	 * @var IConverter[]
	 */
	protected $converters = [];

	/**
	 * Returns the name of the command.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'convert';
	}

	/**
	 * Initializes converter instances once before file processing starts.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function beforeProcessFiles(): void {
		parent::beforeProcessFiles();
		$this->initConverters();
	}

	/**
	 * Instantiates converter services defined in the command configuration.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function initConverters() {
		$this->converters = [];
		$converterFactoryCallbacks = $this->config['converters'];
		foreach ( $converterFactoryCallbacks as $key => $callback ) {
			$converter = call_user_func_array(
				$callback,
				$this->getCallbackArguments()
			);
			if ( $converter instanceof IConverter === false ) {
				throw new Exception(
					"Factory callback for converter '$key' did not return an "
					. "IConverter object"
				);
			}
			if ( $converter instanceof IOutputAwareInterface ) {
				$converter->setOutput( $this->output );
			}
			$this->converters[$key] = $converter;
		}
	}

	/**
	 * Adjusts source and target roots before collecting convertible files.
	 *
	 * @return void
	 */
	protected function makeFileList(): void {
		$this->targetBasePath = $this->src . '/content/wikitext';
		$this->src .= '/content/raw';
		parent::makeFileList();
	}

	/**
	 * Restricts conversion to raw markup source files.
	 *
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		return [ 'mraw' ];
	}

	/**
	 * Stores the absolute target file path for the current conversion output.
	 *
	 * @var string
	 */
	protected $targetPathname = '';

	/**
	 * Instantiates converter callbacks and writes converted file content.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function doProcessFile(): bool {
		$this->makeTargetPathname();
		$this->ensureTargetPath();

		foreach ( $this->converters as $converter ) {
			$result = $converter->convert( $this->currentFile );
			file_put_contents( $this->targetPathname, $result );
		}
		return true;
	}

	/**
	 * Resolves the destination wiki file path from the current source file path.
	 *
	 * @return void
	 */
	private function makeTargetPathname() {
		$this->targetPathname = str_replace(
			$this->src,
			$this->targetBasePath,
			$this->currentFile->getPathname()
		);
		$this->targetPathname = preg_replace( '#\\.mraw$#', '.wiki', $this->targetPathname );
	}

	/**
	 * Creates the destination directory for the current output file when needed.
	 *
	 * @return void
	 */
	private function ensureTargetPath() {
		$baseTargetPath = dirname( $this->targetPathname );
		if ( !file_exists( $baseTargetPath ) ) {
			mkdir( $baseTargetPath, 0755, true );
		}
	}

	/**
	 * Returns constructor arguments passed to converter factory callbacks.
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
