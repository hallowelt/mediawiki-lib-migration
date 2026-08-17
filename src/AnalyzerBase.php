<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

abstract class AnalyzerBase implements IAnalyzer {

	/**
	 * @return IAnalyzer
	 */
	public static function factory(): IAnalyzer {
		return new static();
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function analyze( SplFileInfo $file ): bool {
	}
}
