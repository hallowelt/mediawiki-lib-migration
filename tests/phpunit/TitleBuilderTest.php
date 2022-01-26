<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\TitleBuilder;
use PHPUnit\Framework\TestCase;

class TitleBuilderTest extends TestCase {

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleBuilder::build
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleBuilder::appendTitleSegment
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleBuilder::setNamespace
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleBuilder::invertTitleSegments
	 * @return void
	 */
	public function testBuild() {
		$builder = new TitleBuilder( [] );
		$builder->appendTitleSegment( 'Test1' )->appendTitleSegment( 'Test2/X' );

		$this->assertEquals( 'Test1/Test2,_X', $builder->build() );

		$builder->setNamespace( TitleBuilder::NS_HELP );
		$this->assertEquals( 'Help:Test1/Test2,_X', $builder->build() );

		$builder->invertTitleSegments();
		$this->assertEquals( 'Help:Test2,_X/Test1', $builder->build() );
	}
}
