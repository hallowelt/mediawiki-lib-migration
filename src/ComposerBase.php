<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

abstract class ComposerBase implements IComposer {

	/**
	 * @return IComposer
	 */
	public static function factory(): IComposer {
		return new static();
	}
}
