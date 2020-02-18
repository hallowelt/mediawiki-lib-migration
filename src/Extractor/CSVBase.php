<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Extractor;

use HalloWelt\MediaWiki\Lib\Migration\ExtractorBase;
use SplFileInfo;

abstract class CSVBase extends ExtractorBase {
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

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected function doExtract( SplFileInfo $file ): bool {
		$lines = file( $file->getPathname() );
		$csv = array_map('str_getcsv', $lines );

		$this->currentLineNumber = 0;
		foreach( $csv as $idx => $row ) {
			$this->currentLineNumber = $idx;
			$this->currentLineData = $row;
			if( $this->skipCurrentLine() ) {
				continue;
			}
			$this->doExtractLine();
		}

		return true;
	}

	protected abstract function doExtractLine();

	protected function skipCurrentLine() {
		if( $this->currentLineNumber === 0 && $this->skipHeaderLine() ) {
			return true;
		}

		return false;
	}

	protected function skipHeaderLine() {
		return true;
	}
}