<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;

interface IComposer {

	/**
	 *
	 * @param Builder $builder
	 */
	public function buildXML( Builder $builder );
}
