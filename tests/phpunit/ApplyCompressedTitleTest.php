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

		// pages-titles
		$pagesTitles = $this->getPagesTitlesMap();
		$actualPagesTitlesMap = $apply->toMapValues( $pagesTitles );

		$this->assertEquals(
			$this->getExpectedPagesTitlesMap(),
			$actualPagesTitlesMap
		);

		// title-revisions
		$titleRevisons = $this->getTitleRevisionsMap();
		$actualTitleRevisionsMap = $apply->toMapKeys( $titleRevisons );

		$this->assertEquals(
			$this->getExpectedTitleRevisionsMap(),
			$actualTitleRevisionsMap
		);

	}

	private function getPagesTitlesMap(): array {
		return [
			'123456701---aaa'
				=> 'ABC:Lorem/ipsum/dolor',
			'123456702---bbb'
				=> 'ABC:Lorem/ipsum/dolor-2/sit',
		];
	}

	private function getTitleRevisionsMap(): array {
		return [
			'ABC:Lorem/ipsum/dolor'
				=> [ 'abc-def-1', 'abc-def-2' ],
			'ABC:Lorem/ipsum/dolor-2/sit'
				=> [ 'abc-def-3', 'abc-def-4' ],
		];
	}

	private function getCompressedTitlesMap(): array {
		return [
			'ABC:Lorem'
				=> 'ABC:Lor~1',
			'ABC:Lorem/ipsum'
				=> 'ABC:Lor~1/ips~1',
			'ABC:Lorem/ipsum/dolor'
				=> 'ABC:Lor~1/ips~1/dol~1',
			'ABC:Lorem/ipsum/dolor-2'
				=> 'ABC:Lor~1/ips~1/dol~2',
			'ABC:Lorem/ipsum/dolor-2/sit'
				=> 'ABC:Lor~1/ips~1/dol~2/sit'
		];
	}

	private function getExpectedPagesTitlesMap(): array {
		return [
			'123456701---aaa'
				=> 'ABC:Lor~1/ips~1/dol~1',
			'123456702---bbb'
				=> 'ABC:Lor~1/ips~1/dol~2/sit',
		];
	}

	private function getExpectedTitleRevisionsMap(): array {
		return [
			'ABC:Lor~1/ips~1/dol~1'
				=> [ 'abc-def-1', 'abc-def-2' ],
			'ABC:Lor~1/ips~1/dol~2/sit'
				=> [ 'abc-def-3', 'abc-def-4' ],
		];
	}
}
