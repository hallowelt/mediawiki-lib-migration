<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IExtractor;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
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
		$this->buckets = new DataBuckets( [
			'revision-contents',
			'title-metadata'
		] );
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @return IExtractor
	 */
	public static function factory( $config, Workspace $workspace ) : IExtractor {
		return new static( $config, $workspace );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function extract( SplFileInfo $file ): bool {
		$this->currentFile = $file;
		$this->loadDataBuckets( $file );
		$result = $this->doExtract( $file );
		if( $result ) {
			$this->persistDataBuckets( $file );
		}

		return $result;
	}

	/**
	 * @param SplFileInfor $file
	 * @return bool
	 */
	protected abstract function doExtract( SplFileInfo $file ): bool;

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function loadDataBuckets( SplFileInfo $file) {
		$this->buckets->loadFromWorkspace( $this->workspace );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function persistDataBuckets( SplFileInfo $file ) {
		$this->buckets->saveToWorkspace( $this->workspace );
	}

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
		$this->buckets->addData( 'title-metadata', $titleText, $meta );
	}
}