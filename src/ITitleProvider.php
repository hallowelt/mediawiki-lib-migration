<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

interface ITitleProvider {

	/**
	 * @return string A prefixed title that complies to MediaWiki requirements, like length and
	 * forbidden characters
	 */
	public function getValidPrefixedTitle(): string;
}
