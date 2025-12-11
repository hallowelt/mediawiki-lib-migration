<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Exception;

/**
 * Compress titles with more then 255 chars
 */
class TitleCompressor {

	private $generatedTitles = [];

	private $longestTitles = [];

	private $compressedTitles = [];

	/**
	 * @param array $pagesTitlesMap
	 * @return array
	 */
	public function execute( array $pagesTitlesMap ): array {
		foreach ( $pagesTitlesMap as $confluenceKey => $title ) {
			$this->generatedTitles[] = $title;
		}
		// sort decending. Longest titles have lower index.
		rsort( $this->generatedTitles );

		$this->findLongestTitles();
		$this->compressTitles();

		return $this->compressedTitles;
		// TODO: Replace titles in all global buckets
	}

	/**
	 * @return void
	 */
	private function findLongestTitles(): void {
		$curLongestTitle = '';
		foreach ( $this->generatedTitles as $title ) {
			if ( $curLongestTitle === '' ) {
				// initial title
				$curLongestTitle = $title;
				continue;
			}

			if ( substr( $curLongestTitle, 0, strlen( $title ) ) === $title ) {
				continue;
			}

			$this->longestTitles[] = $curLongestTitle;
			$curLongestTitle = $title;
		}
		$this->longestTitles[] = $curLongestTitle;
	}

	/**
	 * @return void
	 */
	private function compressTitles(): void {
		for ( $index = 0; $index < count( $this->longestTitles ); $index++ ) {
			$title = $this->longestTitles[$index];
			$titleSegments = explode( '/', trim( $title, "/_ " ) );

			$numOfSegments = count( $titleSegments );
			if ( $numOfSegments < 1 ) {
				continue;
			} elseif ( $numOfSegments > 1 ) {
				// Title has title segments between root an leaf part or
				// Title has root an leaf part only
				$this->compressTitleWith2SegmentsAndMore( $titleSegments );
			} else {
				// Title has root part only
				$this->compressTitleWith1Segment( $titleSegments );
			}
		}
	}

	/**
	 * @param array $titleSegments
	 * @return void
	 */
	private function compressTitleWith1Segment( array $titleSegments ): void {
		$rootTitle = array_shift( $titleSegments );
		$namespaceLength = strpos( $rootTitle, ':' );
		$availableLength = 255 + $namespaceLength;
		$compressedRootTitle = $this->compressTitle( '', $rootTitle, $availableLength );

		$this->compressedTitles[$rootTitle] = $compressedRootTitle;
	}

	/**
	 * @param array $titleSegments
	 * @return void
	 */
	private function compressTitleWith2SegmentsAndMore( array $titleSegments ): void {
		$numOfSegments = count( $titleSegments );

		$namespaceLength = strpos( $titleSegments[0], ':' );
		$namespace = substr( $titleSegments[0], 0, $namespaceLength );
		$titleSegments[0] = substr( $titleSegments[0], $namespaceLength + 1 );

		$availableLength = 255;
		$allowedLeafPageLength = ( int )( $availableLength / $numOfSegments );
		$leafPageTitle = array_pop( $titleSegments );

		$availableLength = 255 - strlen( $allowedLeafPageLength );
		$allowedRootLenght = ( int )( $availableLength / $numOfSegments );
		$rootTitle = array_shift( $titleSegments );
		$rootTitle = "{$namespace}:{$rootTitle}";

		if ( strlen( $rootTitle ) > $allowedRootLenght ) {
			// Compress $rootTitle
			$compressedRootTitle = $this->compressTitle(
				'', $rootTitle, $allowedRootLenght + strlen( "{$namespace}:" )
			);
		} else {
			$compressedRootTitle = $rootTitle;
		}

		if ( strlen( $leafPageTitle ) > $allowedLeafPageLength ) {
			// Compress $leafPageTitle
			$compressedLeafPageTitle = $this->compressTitle( '', $leafPageTitle, $allowedLeafPageLength );
		} else {
			$compressedLeafPageTitle = $leafPageTitle;
		}

		$numOfSegments = count( $titleSegments );
		if ( $numOfSegments > 1 ) {
			// Avoid "division by zero"
			$availableLength = 255 - strlen( $compressedRootTitle ) - strlen( $compressedLeafPageTitle );
			$segmentLength = ( int )( $availableLength / $numOfSegments );

			$curTitle = $rootTitle;
			$this->compressedTitles[$rootTitle] = $compressedRootTitle;
			$compressedTitle = '';
			foreach ( $titleSegments as $titleSegment ) {
				$titleKey = "{$curTitle}";
				if ( $titleKey === '' ) {
					$titleKey = $titleSegment;
				} else {
					$titleKey .= "/{$titleSegment}";
				}
				if ( isset( $this->compressedTitles[$titleKey] ) ) {
					$curTitle = $titleKey;
					continue;
				}

				$compressedTitle = $this->compressTitle( $curTitle, $titleSegment, $segmentLength );
				if ( $titleKey !== $compressedTitle ) {
					$this->compressedTitles[$titleKey] = $compressedTitle;
				}
				$curTitle .= "/{$titleSegment}";
			}

			// Apend $leafPageTitle
			if ( $leafPageTitle !== '' ) {
				$curTitle .= "/{$leafPageTitle}";
				$compressedTitle .= "/{$compressedLeafPageTitle}";
				if ( $curTitle !== $compressedTitle ) {
					$this->compressedTitles[$curTitle] = $compressedTitle;
				}
			}
		} else {
			$curTitle = $rootTitle;
			$this->compressedTitles[$curTitle] = $compressedRootTitle;
			$curTitle = "{$rootTitle}/{$leafPageTitle}";
			$this->compressedTitles[$curTitle] = "{$compressedRootTitle}/{$compressedLeafPageTitle}";
		}
	}

	/**
	 * @param string $curTitle
	 * @param string $titleSegment
	 * @param int $segmentLength
	 * @return string
	 */
	private function compressTitle( string $curTitle, string $titleSegment, int $segmentLength ): string {
		$compressedCurTitle = '';
		if ( isset( $this->compressedTitles[$curTitle] ) ) {
			$compressedCurTitle = $this->compressedTitles[$curTitle];
		}

		if ( $compressedCurTitle !== '' ) {
			$compressedCurTitle .= "/";
		}

		$compressedTitle = '';
		$titleCounter = 0;
		$kill = 0;
		do {
			$titleCounter++;
			$kill++;

			// Segment length < $segmentLength => don't compress segment
			// Segment length > $segmentLength => compress segment
			if ( strlen( $titleSegment ) <= $segmentLength ) {
				$compressedTitle = "{$compressedCurTitle}{$titleSegment}";
				break;
			} else {
				$counter = "~" . (string)$titleCounter;
				$counterLength = strlen( $counter );

				$compressedTitleSegment = substr( $titleSegment, 0, $segmentLength - $counterLength );
				$compressedTitleSegment .= $counter;

				$compressedTitle = "{$compressedCurTitle}{$compressedTitleSegment}";
			}
		} while ( $kill <= 1000 && in_array( $compressedTitle, $this->compressedTitles ) );

		if ( $kill > 1000 ) {
			throw new Exception( 'To many loopls in TitleCompressor.' );
		}

		return $compressedTitle;
	}
}
