<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\Migration\Workspace;

/**
 * Presents key-value file storage
 *
 * @package HalloWelt\MediaWiki\Lib\Migration
 */
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
	 * Adds data to data bucket.
	 * There are several cases to use it:
	 * * In case if data bucket already has data:<br/>
	 * <var>$forceArray</var> is set to <tt>false</tt> - data will be replaced.<br/>
	 * <var>$addUnique</var> will not be used in that case.<br/>
	 * If <var>$forceArray</var> is set to <tt>true</tt>, then old data will be wrapped into array
	 *     (if it is not array yet). Afterwards new data will be added to the array.<br/>
	 * ALso If <var>$addUnique</var> is set to <tt>true</tt> - duplicates will be removed from array.
	 * * In case if data bucket has no data yet:<br/>
	 * <var>$forceArray</var> is set to <tt>true</tt> - data will be wrapped into array<br/>
	 * <var>$forceArray</var> is set to <tt>false</tt> - data will be stored as single value
	 * 
	 * @param string $bucketKey
	 * @param string|null $path
	 * @param string|int|boolean|string[]|int[]|boolean[]|array $value
	 * @param bool $forceArray Always create an array as value
	 * @param bool $addUnique Force unique values in te resulting array
	 */
	public function addData( $bucketKey, $path, $value, $forceArray = true, $addUnique = false ) {
		if( $path === null ) {
			$this->buckets[$bucketKey][] = $value;
		}
		else {
			if( $forceArray && !isset( $this->buckets[$bucketKey][$path] ) ) {
				$this->buckets[$bucketKey][$path] = [];
			}
			if( isset( $this->buckets[$bucketKey][$path] ) ) {
				if( !$forceArray ) {
					$this->buckets[$bucketKey][$path] = $value;
				}
				else {
					if( is_array( $this->buckets[$bucketKey][$path] ) === false ) {
						$this->buckets[$bucketKey][$path] = [ $this->buckets[$bucketKey][$path] ];
					}
					$this->buckets[$bucketKey][$path][] = $value;
					if( $addUnique ) {
						$this->buckets[$bucketKey][$path] = array_unique( $this->buckets[$bucketKey][$path] );
					}
				}
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
