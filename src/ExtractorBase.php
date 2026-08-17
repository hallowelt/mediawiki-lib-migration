<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

abstract class ExtractorBase implements IExtractor {

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 * @return IExtractor
	 */
	public static function factory(): IExtractor {
		return new static();
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function extract( SplFileInfo $file ): bool {}
}
