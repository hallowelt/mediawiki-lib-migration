<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
use SplFileInfo;

abstract class CSVBase extends AnalyzerBase {

	/**
	 * @var array
	 */
	protected $currentLineData = [];

	/**
	 * @var int
	 */
	protected $currentLineNumber = 0;

	/**
	 * @inheritDoc
	 */
	protected function doAnalyze( SplFileInfo $file ): bool {
		$lines = file( $file->getPathname() );
		$csv = array_map( 'str_getcsv', $lines );

		$this->currentLineNumber = 0;
		foreach ( $csv as $idx => $row ) {
			$this->currentLineNumber = $idx;
			$this->currentLineData = $row;
			if ( $this->skipCurrentLine() ) {
				continue;
			}
			$this->doAnalyzeLine();
		}

		return true;
	}

	/**
	 * @return bool
	 */
	protected function skipCurrentLine() {
		if ( $this->currentLineNumber === 0 && $this->skipHeaderLine() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function skipHeaderLine() {
		return true;
	}

	/**
	 * @return void
	 */
	abstract protected function doAnalyzeLine();
}
