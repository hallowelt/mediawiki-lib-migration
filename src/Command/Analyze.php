<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use Exception;

class Analyze extends CliCommandBase {
	protected function configure() {
		$this->setName( 'analyze' );
		return parent::configure();
	}

	protected function doProcessFile(): bool {
		$analyzerFactoryCallbacks = $this->config['analyzers'];
		foreach( $analyzerFactoryCallbacks as $key => $callback ) {
			$analyzer = call_user_func_array( $callback, [ $this->config, $this->workspace ] );
			if( $analyzer instanceof IAnalyzer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an IAnalyzer object"
				);
			}
			$result = $analyzer->analyze( $this->currentFile );
			//TODO: Evaluate result
		}
		return true;
	}

}
