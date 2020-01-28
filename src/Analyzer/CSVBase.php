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

	public function doAnalyze( SplFileInfo $file ): bool {
		$fileHandle = fopen( $file->getPathname(), 'r' );
		$csv = array_map('str_getcsv', file($file->getPathname()));
		foreach( $csv as $row ) {
			$this->currentLineData = $row;
			$this->doExtractLine();
		}

		return true;
	}

	protected abstract function doExtractLine();
}