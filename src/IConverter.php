<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

interface IConverter {

	/**
	 *
	 * @param SplFileInfo $file
	 * @return string
	 */
	public function convert( SplFileInfo $file ) : string;
}