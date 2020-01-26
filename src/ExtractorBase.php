<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\IExtractor;

abstract class ExtractorBase implements IExtractor {

	/**
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 *
	 * @param array $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 *
	 * @param array $config
	 * @return IExtractor
	 */
	public static function factory( $config ) : IExtractor {
		return new static( $config );
	}
}