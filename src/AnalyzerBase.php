<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;

abstract class AnalyzerBase implements IAnalyzer {

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
	 */
	public function __construct( $config, Workspace $workspace ) {
		$this->config = $config;
		$this->workspace = $workspace;
		$this->buckets = new DataBuckets( [
			'title-revisions',
			'title-attachments'
		] );
	}

	/**
	 *
	 * @param array $config
	 * @return IAnalyzer
	 */
	public static function factory( $config, Workspace $workspaceDir ) : IAnalyzer {
		return new static( $config, $workspaceDir );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function analyze( SplFileInfo $file ): bool {
		$this->currentFile = $file;
		$this->loadDataBuckets( $file );
		$result = $this->doAnalyze( $file );
		if( $result ) {
			$this->persistDataBuckets( $file );
		}

		return $result;
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected abstract function doAnalyze ( SplFileInfo $file ): bool;

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

	protected function addTitleRevision( $titleText, $contentReference = 'n/a' ) {
		$this->buckets->addData( 'title-revisions', $titleText, $contentReference );
	}

	protected function addTitleAttachment( $titleText, $attachmentReference = 'n/a' ) {
		$this->buckets->addData( 'title-attachments', $titleText, $attachmentReference );
	}
}