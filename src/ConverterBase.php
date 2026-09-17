<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

abstract class ConverterBase implements IConverter {

	/** @var SplFileInfo */
	protected $currentFile = null;

	/**
	 * @return IConverter
	 */
	public static function factory() {
		return new static();
	}

	/**
	 * @param SplFileInfo $file
	 * @return string
	 */
	public function convert( SplFileInfo $file ): string {
		$result = $this->doConvert( $file );
		return $result;
	}

	/**
	 * @param SplFileInfo $file
	 * @return string
	 */
	abstract protected function doConvert( SplFileInfo $file ): string;
}
