<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\TitleCompressor;
use PHPUnit\Framework\TestCase;

class TitleCompressorTest extends TestCase {

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleCompressor::execute
	 * @return void
	 */
	public function testExecute() {
		$titleCompressor = new TitleCompressor();

		$pagesTitlesMap = $this->getPagesTitlesMap();
		$compressedTitles = $titleCompressor->execute( $pagesTitlesMap, 30 );

		$this->assertEquals(
			$this->getExpectedCompressedTitleMap(),
			$compressedTitles
		);
	}

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleCompressor::execute
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'provideUtf8TitlesAtCutBoundary' )]
	public function testUtf8ValidityAfterCompression( string $title, string $expectedCompressed ) {
		$compressor = new TitleCompressor();
		// maxChars=10 → segmentLength=10; the multibyte char straddles the 8-byte cut point
		$result = $compressor->execute( [ 'key' => $title ], 10 );

		foreach ( $result as $compressed ) {
			$this->assertTrue(
				mb_check_encoding( $compressed, 'UTF-8' ),
				"Compressed title is not valid UTF-8: " . bin2hex( $compressed )
			);
		}
		$this->assertSame( $expectedCompressed, $result[$title] );
	}

	public static function provideUtf8TitlesAtCutBoundary(): array {
		return [
			// ü = 2-byte sequence (C3 BC); starts at byte 7, straddles the byte-8 cut
			'2-byte char at cut boundary' => [
				'NS:AAAAAAAüBB',
				'NS:AAAAAAA~1',
			],
			// 中 = 3-byte sequence (E4 B8 AD); starts at byte 7, straddles the byte-8 cut
			'3-byte char at cut boundary' => [
				'NS:AAAAAAA中B',
				'NS:AAAAAAA~1',
			],
		];
	}

	/**
	 * @return array
	 */
	private function getPagesTitlesMap(): array {
		return [
			'123456701---la'
				=> 'ABC:1234567890/1234567890/1234567890/1234567890/1234567890',
			'123456702---b'
				=> 'ABC:1234567890/A1234567890/1234567890/C123456789A',
			'123456702---c'
				=> 'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890',
			'123456703---d'
				=> 'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890',
			'123456704---e'
				=> 'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890',
			'123456705---f'
				=> 'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890',
			'123456706---g'
				=> 'ABC:1234567890/A123456789A/1234567890/C123456789B/1234567890',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedCompressedTitleMap(): array {
		return [
			'ABC:1234567890' => 'ABC:123~1',
			'ABC:1234567890/1234567890' => 'ABC:123~1/123~1',
			'ABC:1234567890/1234567890/1234567890' => 'ABC:123~1/123~1/123~1',
			'ABC:1234567890/1234567890/1234567890/1234567890' => 'ABC:123~1/123~1/123~1/123~1',
			'ABC:1234567890/1234567890/1234567890/1234567890/1234567890' => 'ABC:123~1/123~1/123~1/123~1/123~1',
			'ABC:1234567890/A1234567890' => 'ABC:123~1/A12~1',
			'ABC:1234567890/A1234567890/1234567890' => 'ABC:123~1/A12~1/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789A' => 'ABC:123~1/A12~1/123~1/C12~2',
			'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890' => 'ABC:123~1/A12~1/123~1/C12~2/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789B' => 'ABC:123~1/A12~1/123~1/C12~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890' => 'ABC:123~1/A12~1/123~1/C12~1/123~1',
			'ABC:1234567890/A123456789A' => 'ABC:123~1/A12~2',
			'ABC:1234567890/A123456789A/1234567890' => 'ABC:123~1/A12~2/123~1',
			'ABC:1234567890/A123456789A/1234567890/C123456789B' => 'ABC:123~1/A12~2/123~1/C12~1',
			'ABC:1234567890/A123456789A/1234567890/C123456789B/1234567890' => 'ABC:123~1/A12~2/123~1/C12~1/123~1',
			'ABC:1234567890/B123456789A' => 'ABC:123~1/B12~2',
			'ABC:1234567890/B123456789A/1234567890' => 'ABC:123~1/B12~2/123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890' => 'ABC:123~1/B12~2/123~1/123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~2/123~1/123~1/123~1',
			'ABC:1234567890/B123456789B' => 'ABC:123~1/B12~1',
			'ABC:1234567890/B123456789B/1234567890' => 'ABC:123~1/B12~1/123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890' => 'ABC:123~1/B12~1/123~1/123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~1/123~1/123~1/123~1',
		];
	}
}
