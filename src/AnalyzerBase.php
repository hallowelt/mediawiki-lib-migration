<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

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
	public static function factory( $config, Workspace $workspace, DataBuckets $buckets ) : IAnalyzer {
		return new static( $config, $workspace, $buckets );
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
	abstract protected function doAnalyze( SplFileInfo $file ): bool;

	/**
	 *
	 * @param string $titleText
	 * @param string $contentReference
	 * @return void
	 */
	protected function addTitleRevision( $titleText, $contentReference = 'n/a' ) {
		$this->buckets->addData( 'title-revisions', $titleText, $contentReference );
	}

	/**
	 *
	 * @param string $titleText
	 * @param string $attachmentReference
	 * @return void
	 */
	protected function addTitleAttachment( $titleText, $attachmentReference = 'n/a' ) {
		$this->buckets->addData( 'title-attachments', $titleText, $attachmentReference );
	}

	/**
	 *
	 * @param string $rawFilename
	 * @param string $attachmentReference
	 * @return void
	 */
	protected function addFile( $rawFilename, $attachmentReference = 'n/a' ) {
		try {
			$filename = $this->getFilename( $rawFilename, $attachmentReference );
			$filename = ( new WindowsFilename( $filename ) ) . '';
		} catch ( InvalidTitleException $ex ) {
			$this->logger->error( $ex->getMessage() );
			return;
		}

		$prefixedFilename = $this->maybePrefixFilename( $filename );

		$this->buckets->addData( 'files', $prefixedFilename, $attachmentReference );
	}

	/**
	 *
	 * @param string $filename
	 * @return void
	 */
	protected function maybePrefixFilename( $filename ) {
		return $filename;
	}

	/**
	 *
	 * @var array
	 */
	protected $rawFilenameReferenceMap = [];

	/**
	 *
	 * @param string $rawFilename
	 * @param string $attachmentReference
	 * @return void
	 */
	protected function getFilename( $rawFilename, $attachmentReference ) {
		if ( isset( $this->rawFilenameReferenceMap[$rawFilename] ) ) {
			if ( $this->rawFilenameReferenceMap[$rawFilename] !== $attachmentReference ) {
				$rawFilename = $this->uncollideFilename( $rawFilename, $attachmentReference );
			}
		}

		$this->rawFilenameReferenceMap[$rawFilename] = $attachmentReference;
		return $rawFilename;
	}

	/**
	 *
	 * @param string $rawFilename
	 * @param string $attachmentReference
	 * @return void
	 */
	protected function uncollideFilename( $rawFilename, $attachmentReference ) {
		$parts = explode( '.', $rawFilename );
		$fileExt = array_pop( $parts );
		$plainFilename = implode( '.', $parts );
		$count = 0;
		foreach ( $this->rawFilenameReferenceMap as $filename => $attachmentRef ) {
			if ( strpos( $filename, $plainFilename ) === 0 ) {
				if ( $attachmentReference !== $attachmentRef ) {
					$count++;
				}
			}
		}
		$suffix = '';
		if ( $count > 0 ) {
			$suffix = $count;
		}

		return $plainFilename . $suffix . '.' . $fileExt;
	}
}
