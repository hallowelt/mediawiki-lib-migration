<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

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
	 * @var array
	 */
	protected $analyzeData = [];

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
		$this->loadAnalyzeData( $file );
		$result = $this->doAnalyze( $file );
		if( $result ) {
			$this->persistAnalyzeData( $file );
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
	protected function loadAnalyzeData( SplFileInfo $file) {
		$buckets = $this->getDataBuckets( $file );
		foreach( $buckets as $bucketKey => $bucketWorkspaceFilename ) {
			$this->analyzeData[$bucketKey] = $this->workspace->loadData( $bucketWorkspaceFilename );
		}
	}

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function persistAnalyzeData( SplFileInfo $file ) {
		$buckets = $this->getDataBuckets( $file );
		foreach( $buckets as $bucketKey => $bucketWorkspaceFilename ) {
			$this->workspace->saveData( $bucketWorkspaceFilename, $this->analyzeData[$bucketKey] );
		}
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return array
	 */
	protected function getDataBuckets( SplFileInfo $file ) {
		return [
			'title-revisions' => 'title-revisions',
			'title-attachments' => 'title-attachments'
		];
	}

	/**
	 *
	 * @param string $bucketKey
	 * @param string|null $path
	 * @param string|int|boolean|string[]|int[]|boolean[]|array $value
	 */
	protected function addData( $bucketKey, $path, $value ) {
		if( $path === null ) {
			$this->analyzeData[$bucketKey][] = $value;
		}
		else {
			if( isset( $this->analyzeData[$bucketKey][$path] ) ) {
				if( is_array( $this->analyzeData[$bucketKey][$path] ) === false ) {
					$this->analyzeData[$bucketKey][$path] = [ $this->analyzeData[$bucketKey][$path] ];
				}
				$this->analyzeData[$bucketKey][$path][] = $value;
			}
			else {
				//TODO: Implement $path resolution!
				$this->analyzeData[$bucketKey][$path] = $value;
			}
		}
	}
}