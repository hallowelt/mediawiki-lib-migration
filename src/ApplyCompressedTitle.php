<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

/**
 * 
 */
class ApplyCompressedTitle {

	/** @var array */
	private $compressedTitleMap = [];

	public function __construct( array $compressedTitleMap ) {
		$this->compressedTitleMap = $compressedTitleMap;
	}

	/**
	 * @param array $map
	 * @return array
	 */
	public function toMapKeys( array $map ): array {
		$newMap = [];

		foreach ( $map as $title => $values ) {
			$newKey = $this->getTitle( $title );
			$newMap[$newKey] = $values;
		}

		return $newMap;
	}

	/**
	 * @param array $map
	 * @return array
	 */
	public function toMapValues( array $map ): array {
		$newMap = [];

		foreach ( $map as $key => $title ) {
			$newMap[$key] = $this->getTitle( $title );
		}

		return $newMap;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function getTitle( string $title ): string {
		$segments = explode( '/', $title );
		$numOfSegments = count( $segments );
		$tail = '';
		for ( $index = 0; $index < $numOfSegments; $index++ ) {
			$test = implode( '/', $segments );
			if ( isset( $this->compressedTitleMap[$test] ) ) {
				return $this->compressedTitleMap[$test] . $tail;
			}
			$lastSegment = array_pop( $segments );
			$tail = "/{$lastSegment}{$tail}";
		}
		return $title;
	}

}
