<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\Workspace;

class DataBuckets {



	/**
	 *
	 * @var array
	 */
	protected $buckets = [];

	/**
	 *
	 * @param string[] $bucketKeys
	 */
	public function __construct( $bucketKeys ) {
		foreach( $bucketKeys as $bucketKey ) {
			$this->buckets[$bucketKey] = [];
		}
	}

	/**
	 *
	 * @param string $bucketKey
	 * @param string|null $path
	 * @param string|int|boolean|string[]|int[]|boolean[]|array $value
	 * @param bool $forceArray Always create an array as value
	 */
	public function addData( $bucketKey, $path, $value, $forceArray = true ) {
		if( $path === null ) {
			$this->buckets[$bucketKey][] = $value;
		}
		else {
			if( $forceArray && !isset( $this->buckets[$bucketKey][$path] ) ) {
				$this->buckets[$bucketKey][$path] = [];
			}
			if( isset( $this->buckets[$bucketKey][$path] ) ) {
				if( is_array( $this->buckets[$bucketKey][$path] ) === false ) {
					$this->buckets[$bucketKey][$path] = [ $this->buckets[$bucketKey][$path] ];
				}
				$this->buckets[$bucketKey][$path][] = $value;
			}
			else {
				//TODO: Implement $path resolution!
				$this->buckets[$bucketKey][$path] = $value;
			}
		}
	}

	/**
	 *
	 * @param string $buckedKey
	 * @param array $data
	 */
	public function setBucketData( $buckedKey, $data ) {
		$this->buckets[$buckedKey] = $data;
	}

	/**
	 *
	 * @param string $bucketKey
	 * @return array
	 */
	public function getBucketData( $bucketKey ) {
		return $this->buckets[$bucketKey];
	}

	/**
	 *
	 * @param Workspace $workspace
	 */
	public function saveToWorkspace( Workspace $workspace ) {
		foreach( $this->buckets as $bucketKey => $data ) {
			$workspace->saveData( $bucketKey, $this->buckets[$bucketKey] );
		}
	}

	/**
	 *
	 * @param Workspace $workspace
	 */
	public function loadFromWorkspace( Workspace $workspace ) {
		foreach( $this->buckets as $bucketKey => $data ) {
			$this->buckets[$bucketKey] = $workspace->loadData( $bucketKey );
		}
	}

}
