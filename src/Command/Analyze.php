<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use Exception;

class Analyze extends CliCommandBase {
	protected function configure() {
		$this->setName( 'analyze' );
		return parent::configure();
	}

	protected function getBucketKeys() {
		return [
			'files',
			'filename-collisions',
			'title-attachments',
			'title-collisions',
			'title-invalids',
			'title-revisions'
		];
	}

	protected function beforeProcessFiles() {
		parent::beforeProcessFiles();
		//Explicitly reset the persisted data
		$this->buckets = new DataBuckets( $this->getBucketKeys() );
	}

	protected function doProcessFile(): bool {
		$analyzerFactoryCallbacks = $this->config['analyzers'];
		foreach( $analyzerFactoryCallbacks as $key => $callback ) {
			$analyzer = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
			);
			if( $analyzer instanceof IAnalyzer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IAnalyzer object"
				);
			}
			$result = $analyzer->analyze( $this->currentFile );
			//TODO: Evaluate result
		}
		return true;
	}

}
