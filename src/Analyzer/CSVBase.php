<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
use SplFileInfo;

abstract class CSVBase extends AnalyzerBase {

	/**
	 *
	 * @var array
	 */
	protected $currentLineData = [];

	/**
	 *
	 * @var int
	 */
	protected $currentLineNumber = 0;

	public function doAnalyze( SplFileInfo $file ): bool {
		$lines = file( $file->getPathname() );
		$csv = array_map( 'str_getcsv', $lines );

		$this->currentLineNumber = 0;
		foreach( $csv as $idx => $row ) {
			$this->currentLineNumber = $idx;
			$this->currentLineData = $row;
			if( $this->skipCurrentLine() ) {
				continue;
			}
			$this->doAnalyzeLine();
		}

		return true;
	}

	protected function skipCurrentLine() {
		if( $this->currentLineNumber === 0 && $this->skipHeaderLine() ) {
			return true;
		}

		return false;
	}

	protected function skipHeaderLine() {
		return true;
	}

	protected abstract function doAnalyzeLine();
}