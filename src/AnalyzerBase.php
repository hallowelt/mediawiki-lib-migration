<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileObject;

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
	 * @var SplFileObject
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
	 * @param SplFileObject $file
	 * @return bool
	 */
	public function analyze( SplFileObject $file ): bool {
		$this->currentFile = $file;
		$this->loadAnalyzeData( $file );
		$result = $this->doAnalyze( $file );
		if( $result ) {
			$this->persistAnalyzeData( $file );
		}

		return $result;
	}

	/**
	 * @param SplFileObject $file
	 * @return bool
	 */
	protected abstract function doAnalyze ( SplFileObject $file ): bool;

	/**
	 *
	 * @param SplFileObject $file
	 */
	protected function loadAnalyzeData( SplFileObject $file) {
		$buckets = $this->getDataBuckets( $file );
		foreach( $buckets as $bucketKey => $bucketWorkspaceFilename ) {
			$this->analyzeData[$bucketKey] = $this->workspace->loadData( $bucketWorkspaceFilename );
		}
	}

	/**
	 *
	 * @param SplFileObject $file
	 */
	protected function persistAnalyzeData( SplFileObject $file ) {
		$buckets = $this->getDataBuckets( $file );
		foreach( $buckets as $bucketKey => $bucketWorkspaceFilename ) {
			$this->workspace->saveData( $bucketWorkspaceFilename, $this->analyzeData[$bucketKey] );
		}
	}

	protected function getDataBuckets( SplFileObject $file ) {
		return [
			'title-metadata' => 'title-metadata',
			'title-revisions' => 'title-revisions',
			'title-attachments' => 'title-attachments',
			'file-metadata' => 'file-metadata',
			'file-sha1-map' => 'file-sha1-map',
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