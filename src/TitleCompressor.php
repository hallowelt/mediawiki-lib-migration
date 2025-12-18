<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Exception;

/**
 * Compress titles with more then 255 chars
 */
class TitleCompressor {

	private $maxChars = 255;

	private $input = [];

	private $compressedTitles = [];

	/**
	 * @param array $map
	 * @return array
	 */
	public function execute( array $map, int $maxChars = 255 ): array {
		$this->maxChars = $maxChars;

		foreach ( $map as $key => $title ) {
			$this->input[] = $title;
		}
		// sort decending. Longest titles have lower index.

		$this->compressTitles();
		$compressedMap = $this->makeTitleMap();
		#file_put_contents( '/datadisk/workspace/migrate-confluence/debug-compressed-titles-2.log', var_export( $this->compressedTitles, true ) );

		return $compressedMap;
	}

	private function makeTitleMap(): array {
		$map = [];
		foreach ( $this->input as $originalTitle ) {
			$searchTitle = '';
			$newTitle = '';
			$segments = explode( '/', $originalTitle );

			foreach ( $segments as $segment ) {
				if ( $newTitle !== '' ) {
					$newTitle .= '/';
				}
				if ( $searchTitle !== '' ) {
					$searchTitle .= '/';
				}
				$searchTitle .= $segment;
				if ( isset( $this->compressedTitles[$searchTitle] ) ) {
					$newTitle .= $this->compressedTitles[$searchTitle];
				} else {
					$newTitle .= $searchTitle;
				}
			}
			$map[$originalTitle] = $newTitle;
		}
		return $map;
	}


	private function compressTitles() {
		for ( $index = 0; $index < count( $this->input ); $index++ ) {
			$title = $this->input[$index];
			$namespace = '';
			if ( str_contains( $title, ':' ) ) {
				$namespace = substr( $title, 0, strpos( $title, ':' ) + 1 );
				$title = substr( $title, strpos( $title, ':' ) + 1 );
			}

			$segments = explode( '/', $title );
			$numOfSegments = count( $segments );

			$numOfSlashes = $numOfSegments - 1;
			$segmentLength = (int)( ( $this->maxChars - $numOfSlashes ) / $numOfSegments );

			$curTitle = '';
			$curCompressedTitle = '';
			foreach ( $segments as $segment ) {
				if ( $curTitle === '' ) {
					$curTitle = "{$namespace}{$segment}";
				} else {
					$curTitle .=  "/{$segment}";
				}

				if ( strlen( $segment ) > $segmentLength ) {
					$segment = $this->compressTitle( $curTitle, $segment, $segmentLength );
				}

				if ( $curCompressedTitle === '' ) {
					$curCompressedTitle = "{$namespace}{$segment}";
					$segment = "{$namespace}{$segment}";
				} else {
					$curCompressedTitle .=  "/{$segment}";
				}

				$this->compressedTitles[$curTitle] = $segment;
			}

		}
	}


	/**
	 * @param string $titleSegment
	 * @param int $segmentLength
	 * @return string
	 */
	private function compressTitle( string $curTitle, string $titleSegment, int $segmentLength ): string {
		$titleCounter = 0;
		$kill = 0;
		do {
			$titleCounter++;
			$kill++;

			// Segment length < $segmentLength => don't compress segment
			// Segment length > $segmentLength => compress segment
			if ( strlen( $titleSegment ) <= $segmentLength ) {
				$compressedTitleSegment = "{$titleSegment}";
				break;
			} else {
				$counter = "~" . (string)$titleCounter;
				$counterLength = strlen( $counter );

				$compressedTitleSegment = substr( $titleSegment, 0, $segmentLength - $counterLength );
				$compressedTitleSegment .= $counter;

				$nextRound = false;
				foreach ( $this->compressedTitles as $key => $existingTitle ) {
					if ( substr_count( $key, '/' ) !== substr_count( $curTitle, '/' ) ) {
						// different subpage level
						continue;
					}
					if ( substr( $key, 0, strrpos( $key, '/' ) ) !== substr( $curTitle, 0, strrpos( $curTitle, '/' ) ) ) {
						// different page root
						continue;
					} else if ( substr( $key, strrpos( $key, '/' ) ) !== substr( $curTitle, strrpos( $curTitle, '/' ) ) ) {
						// same subpage level but different subpagename
						// collision alert!
						if ( $existingTitle === $compressedTitleSegment ) {
							$nextRound = true;
							break;
						}
					} else {
						// same subpage level, same subage name
						if ( strlen( $existingTitle ) <= strlen( $compressedTitleSegment ) ) {
							$compressedTitleSegment = $existingTitle;
							break;
						}
					}
				}
			}
		} while ( $kill <= 1000 && $nextRound );

		if ( $kill > 1000 ) {
			throw new Exception( 'To many loopls in TitleCompressor.' );
		}

		return $compressedTitleSegment;
	}
}
