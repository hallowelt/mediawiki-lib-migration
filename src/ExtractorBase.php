<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use SplFileInfo;

abstract class ExtractorBase implements IExtractor {

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
	 * @var DataBuckets
	 */
	protected $analyzeBuckets = null;

	/**
	 *
	 * @var SplFileInfo
	 */
	protected $currentFile = null;

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
		$this->analyzeBuckets = new DataBuckets( [
			'files',
			'filename-collisions',
			'title-attachments',
			'title-collisions',
			'title-invalids',
			'title-revisions'
		] );
		$this->analyzeBuckets->loadFromWorkspace( $workspace );
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 * @return IExtractor
	 */
	public static function factory( $config, Workspace $workspace, DataBuckets $buckets ) : IExtractor {
		return new static( $config, $workspace, $buckets );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function extract( SplFileInfo $file ): bool {
		$this->currentFile = $file;
		$result = $this->doExtract( $file );
		return $result;
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	abstract protected function doExtract( SplFileInfo $file ): bool;

	/**
	 *
	 * @param string $revisionReference
	 * @param string $contentReference
	 */
	protected function addRevisionContent( $revisionReference, $contentReference = 'n/a' ) {
		$this->buckets->addData( 'revision-contents', $revisionReference, $contentReference );
	}

	/**
	 *
	 * @param string $titleText
	 * @param string $meta
	 */
	protected function addTitleMetaData( $titleText, $meta = [] ) {
		$this->buckets->addData( 'title-metadata', $titleText, $meta, false );
	}
}
