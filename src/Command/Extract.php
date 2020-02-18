<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IExtractor;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use Exception;

class Extract extends CliCommandBase {
	protected function configure() {
		$this->setName( 'extract' );
		return parent::configure();
	}

	protected function getBucketKeys() {
		return [
			'revision-contents',
			'title-metadata',
			'file-merges',
			'filename-collisions'
		];
	}

	protected function beforeProcessFiles() {
		parent::beforeProcessFiles();
		//Explicitly reset the persisted data
		$this->buckets = new DataBuckets( $this->getBucketKeys() );
	}

	protected function doProcessFile(): bool {
		$extractorFactoryCallbacks = $this->config['extractors'];
		foreach( $extractorFactoryCallbacks as $key => $callback ) {
			$extractor = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
			);
			if( $extractor instanceof IExtractor === false ) {
				throw new Exception(
					"Factory callback for extractor '$key' did not return an "
					. "IExtractor object"
				);
			}
			$result = $extractor->extract( $this->currentFile );
			//TODO: Evaluate result
		}
		return true;
	}
}
