<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IConverter;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;

class Convert extends CliCommandBase {

	/**
	 *
	 * @var string
	 */
	private $targetBasePath = '';

	protected function configure() {
		$this->setName( 'convert' );
		return parent::configure();
	}

	protected function makeFileList() {
		$this->targetBasePath = $this->src . '/content/wikitext';
		$this->src .= '/content/raw';
		return parent::makeFileList();
	}

	protected function makeExtensionWhitelist(): array {
		return [ 'mraw' ];
	}

	/**
	 * @var string
	 */
	protected $targetPathname = '';

	protected function doProcessFile(): bool {
		$converterFactoryCallbacks = $this->config['converters'];
		$this->makeTargetPathname();
		$this->ensureTargetPath();

		foreach ( $converterFactoryCallbacks as $key => $callback ) {
			$converter = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace ]
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
			$result = $converter->convert( $this->currentFile );
			file_put_contents( $this->targetPathname, $result );
		}
		return true;
	}

	private function makeTargetPathname() {
		$this->targetPathname = str_replace(
			$this->src,
			$this->targetBasePath,
			$this->currentFile->getPathname()
		);
		$this->targetPathname = preg_replace( '#\.mraw$#', '.wiki', $this->targetPathname );
	}

	private function ensureTargetPath() {
		$baseTargetPath = dirname( $this->targetPathname );
		if ( !file_exists( $baseTargetPath ) ) {
			mkdir( $baseTargetPath, 0755, true );
		}
	}

}
