<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

abstract class ComposerBase implements IComposer {

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
	 * @var DataBuckets
	 */
	protected $buckets = null;

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		$this->config = $config;
		$this->workspace = $workspace;
		$this->buckets = $buckets;
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 * @return IComposer
	 */
	public static function factory( $config, Workspace $workspace, DataBuckets $buckets ) : IComposer {
		return new static( $config, $workspace, $buckets );
	}
}