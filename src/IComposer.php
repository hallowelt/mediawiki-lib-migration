<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

interface IComposer {

	/**
	 *
	 * @param string $pagename
	 * @return string[]
	 */
	public function composeRevisionTexts( string $pagename ) : array;
}