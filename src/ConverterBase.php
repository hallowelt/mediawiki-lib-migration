<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

abstract class ConverterBase implements IConverter {

	/**
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 *
	 * @var Workspace
	 */
	protected $workspace = null;

	/**
	 *
	 * @var SplFileInfo
	 */
	protected $currentFile = null;

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 */
	public function __construct( $config, Workspace $workspace ) {
		$this->config = $config;
		$this->workspace = $workspace;
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @return IConverter
	 */
	public static function factory( $config, Workspace $workspace ) {
		return new static( $config, $workspace );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return string
	 */
	public function convert( SplFileInfo $file ): string {
		$result = $this->doConvert( $file );
		return $result;
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return string
	 */
	abstract protected function doConvert( SplFileInfo $file ): string;
}
