<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IExtractor;
use HalloWelt\MediaWiki\Lib\Migration\IFileProcessorEventHandler;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;

class Extract extends CliCommandBase {

	/**
	 *
	 * @var IExtractor[]
	 */
	protected $extractors = [];

	/**
	 *
	 * @inheritDoc
	 */
	protected function configure() {
		$this->setName( 'extract' );
		return parent::configure();
	}

	/**
	 *
	 * @inheritDoc
	 */
	protected function getBucketKeys() {
		return [
			// From this step
			'revision-contents',
			'title-metadata',
			'file-merges',
			'filename-collisions'
		];
	}

	protected function beforeProcessFiles() {
		parent::beforeProcessFiles();
		// Explicitly reset the persisted data
		$this->buckets = new DataBuckets( $this->getBucketKeys() );

		$extractorFactoryCallbacks = $this->config['extractors'];
		foreach ( $extractorFactoryCallbacks as $key => $callback ) {
			$extractor = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
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

	protected function doProcessFile(): bool {
		foreach ( $this->extractors as $key => $extractor ) {
			$result = $extractor->extract( $this->currentFile );
			// TODO: Evaluate result
		}
		return true;
	}
}
