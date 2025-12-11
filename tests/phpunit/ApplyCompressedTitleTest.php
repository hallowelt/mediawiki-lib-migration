<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\ApplyCompressedTitle;
use PHPUnit\Framework\TestCase;

class ApplyCompressedTitleTest extends TestCase {

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\ApplyCompressedTitle::toMapValue
	 * @return void
	 */
	public function testToMapValue() {
		$apply = new ApplyCompressedTitle( $this->getCompressedTitlesMap() );

		$actualPagesTitlesMap = $apply->toMapValues( $this->getPagesTitlesMap() );

		$this->assertEquals(
			$this->getExpectedPagesTitlesMap(),
			$actualPagesTitlesMap
		);
	}

	private function getPagesTitlesMap(): array {
		return [
			'123456701---aaa'
				=> 'ABC:aaa',
			'123456702---bbb'
				=> 'ABC:aaa/bbb',
			'123456703---ccc'
				=> 'ABC:aaa/bbb/ccc',
			'123456704---ccc'
				=> 'ABC:aaa/bbb-2/ccc',
			'123456705---ddd'
				=> 'ABC:aaa/bbb-2/ccc/ddd',
			'123456706---eee'
				=> 'ABC:aaa/bbb-2/ccc/eee',
			'123456707---eee'
				=> 'ABC:aaa/bb/ccc/eee',
		];
	}

	private function getCompressedTitlesMap(): array {
		return [
			'ABC:aaa'
				=> 'ABC:aaa',
			'ABC:aaa/bbb'
				=> 'ABC:aaa/bb~1',
			'ABC:aaa/bbb/ccc'
				=> 'ABC:aaa/bb~1/ccc',
			'ABC:aaa/bbb-2'
				=> 'ABC:aaa/bb~2',
			'ABC:aaa/bbb-2/ccc'
				=> 'ABC:aaa/bb~2/ccc',
		];
	}

	private function getExpectedPagesTitlesMap(): array {
		return [
			'123456701---aaa'
				=> 'ABC:aaa',
			'123456702---bbb'
				=> 'ABC:aaa/bb~1',
			'123456703---ccc'
				=> 'ABC:aaa/bb~1/ccc',
			'123456704---ccc'
				=> 'ABC:aaa/bb~2/ccc',
			'123456705---ddd'
				=> 'ABC:aaa/bb~2/ccc/ddd',
			'123456706---eee'
				=> 'ABC:aaa/bb~2/ccc/eee',
			'123456707---eee'
				=> 'ABC:aaa/bb/ccc/eee',
		];
	}
}
