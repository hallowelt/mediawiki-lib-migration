<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileObject;

interface IExtractor {

	/**
	 *
	 * @param SplFileObject $file
	 * @return bool
	 */
	public function extract( SplFileObject $file ): bool;
}