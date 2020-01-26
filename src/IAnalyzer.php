<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileObject;

interface IAnalyzer {

	/**
	 *
	 * @param SplFileObject $file
	 * @return bool
	 */
	public function analyze( SplFileObject $file ): bool;
}