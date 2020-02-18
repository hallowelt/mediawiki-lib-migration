<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MediaWiki\Lib\Migration\WindowsFilename;
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
	 * @return IAnalyzer
	 */
	public static function factory( $config, Workspace $workspaceDir, DataBuckets $buckets ) : IAnalyzer {
		return new static( $config, $workspaceDir, $buckets );
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function analyze( SplFileInfo $file ): bool {
		$this->currentFile = $file;
		$result = $this->doAnalyze( $file );
		return $result;
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected abstract function doAnalyze ( SplFileInfo $file ): bool;

	protected function addTitleRevision( $titleText, $contentReference = 'n/a' ) {
		$this->buckets->addData( 'title-revisions', $titleText, $contentReference );
	}

	protected function addTitleAttachment( $titleText, $attachmentReference = 'n/a' ) {
		$this->buckets->addData( 'title-attachments', $titleText, $attachmentReference );
	}

	protected function addFile( $rawFilename, $attachmentReference = 'n/a' ) {
		$filename = ( new WindowsFilename( $rawFilename ) ) .'';

		$this->buckets->addData( 'files', $filename, $attachmentReference );
	}
}