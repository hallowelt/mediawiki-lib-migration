<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

interface IAnalyzer {

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function analyze( SplFileInfo $file ): bool;
}
