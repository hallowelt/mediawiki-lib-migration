<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

interface IExtractor {

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function extract( SplFileInfo $file ): bool;
}
