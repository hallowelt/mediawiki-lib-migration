<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

interface IMetadataProvider {

	/**
	 * @return array A set of metadata of a page. Can be any hashmap. Even a nested one. Used within
	 * steps "Analyze" or "Extract". Steps "Convert" or "Compile" will are responsible to evaluate
	 * this data
	 */
	public function getMetaData() : array;
}
