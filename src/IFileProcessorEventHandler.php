<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

interface IFileProcessorEventHandler {

	/**
	 *
	 * @param SplFileInfo $file
	 * @return void
	 */
	public function beforeProcessFiles( SplFileInfo $file );

	/**
	 *
	 * @param SplFileInfo $file
	 * @return void
	 */
	public function afterProcessFiles( SplFileInfo $file );

}
