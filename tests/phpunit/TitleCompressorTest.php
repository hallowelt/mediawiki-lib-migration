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
	private function getPagesTitlesMap(): array {
		return [
			'123456701---long root'
				=> 'ABC:1234567890/1234567890/1234567890/1234567890/1234567890',
			'123456702---one subpage, short root'
				=> 'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890',
			'123456703---one subpage, long root'
				=> 'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890',
			'123456704---more then 2 segments'
				=> 'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890',
			'123456705---more then 2 segments'
				=> 'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890',
			'123456706---more then 2 segments'
				=> 'ABC:1234567890/A123456789A/1234567890/C123456789B/1234567890',
		];
	}

	private function getExpectedCompressedTitleMap(): array {
		return [
			'ABC:1234567890/1234567890/1234567890/1234567890/1234567890' => 'ABC:123~1/123~1/123~1/123~1/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890' => 'ABC:123~1/A12~1/123~1/C12~1/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890' => 'ABC:123~1/A12~1/123~1/C12~2/123~1',
			'ABC:1234567890/A123456789A/1234567890/C123456789B/1234567890' => 'ABC:123~1/A12~2/123~1/C12~1/123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~1/123~1/123~1/123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~2/123~1/123~1/123~1',
		];
	}
}
