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
		];
	}

	private function getExpectedCompressedTitleMap(): array {
		return [
			'ABC:1234567890/1234567890/1234567890/1234567890/1234567890' => 'ABC:123~1/123~1/123~1/123~1/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890' => 'ABC:123~1/A12~1/123~1/C12~1/123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890' => 'ABC:123~1/A12~1/123~1/C12~2/123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~1/123~1/123~1/123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890' => 'ABC:123~1/B12~2/123~1/123~1/123~1',
		];
	}

	private function getExpectedCompressedTitleMap_step(): array {
		return[
			'ABC:1234567890' => 'ABC:123~1',
			'ABC:1234567890/1234567890' => '123~1',
			'ABC:1234567890/1234567890/1234567890' => '123~1',
			'ABC:1234567890/1234567890/1234567890/1234567890' => '123~1',
			'ABC:1234567890/1234567890/1234567890/1234567890/1234567890' => '123~1',
			'ABC:1234567890/A1234567890' => 'A12~1',
			'ABC:1234567890/A1234567890/1234567890' => '123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789A' => 'C12~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789A/1234567890' => '123~1',
			'ABC:1234567890/A1234567890/1234567890/C123456789B' => 'C12~2',
			'ABC:1234567890/A1234567890/1234567890/C123456789B/1234567890' => '123~1',
			'ABC:1234567890/B123456789A' => 'B12~1',
			'ABC:1234567890/B123456789A/1234567890' => '123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890' => '123~1',
			'ABC:1234567890/B123456789A/1234567890/1234567890/1234567890' => '123~1',
			'ABC:1234567890/B123456789B' => 'B12~2',
			'ABC:1234567890/B123456789B/1234567890' => '123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890' => '123~1',
			'ABC:1234567890/B123456789B/1234567890/1234567890/1234567890' => '123~1',

		];
	}

	private function getPagesTitlesMap_bak(): array {
		return [
			'123456701---long root'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
					. '_modification_after_analyze_process_by_human_operator_because_this_would_take_a_lot_of_time'
					. '_sometimes',
			'123456702---one subpage, short root'
				=> 'ABC:A_very_very_very_long_root_page_title/'
					. 'exeeding_255_characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration'
					. '_TitleCompressor.php_which_should_reduce_manual_title_modification_after_analyze_process'
					. '_by_human_operator_because_this_would_take_a_lot_of_time_sometimes',
			'123456703---one subpage, long root'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_'
					. 'title_modification_after_analyze_process_by_human_operator/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'123456704---more then 2 segments'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration'
					. '_TitleCompressor.php/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'123456705---more then 2 segments'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration'
					. '_TitleCompressor.php part 2/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator but a very very very much longer'
					. ' subpage title/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'123456706---no namespace'
				=> 'A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
					. '_modification_after_analyze_process_by_human_operator_because_this_would_take_a_lot_of_time'
					. '_sometimes',
		];
	}

	private function getExpectedCompressedTitleMap_bak(): array {
		return [
			'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression_algorithm'
			. '_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title_modification_after'
			. '_analyze_process_by_human_operator_because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression_'
					. 'algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
					. '_modification_after_analyze_process_by_human_operator_because_this_would_ta~1',
			'ABC:A_very_very_very_long_root_page_title'
				=> 'ABC:A_very_very_very_long_root_page_title',
			'ABC:A_very_very_very_long_root_page_title/'
			. 'exeeding_255_characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration'
			. '_TitleCompressor.php_which_should_reduce_manual_title_modification_after_analyze_process_by_human'
			. '_operator_because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title/'
					. 'exeeding_255_characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration'
					. '_TitleCompressor.php_which_should_redu~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
			. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
			. '_modification_after_analyze_process_by_human_operator'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migratio~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
			. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
			. '_modification_after_analyze_process_by_human_operator/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migratio~1/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~2',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~1/'
					. 'characters_to_test_title_compression_algorithm_~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~1/'
				. 'characters_to_test_title_compression_algorithm_~1/'
				. 'which_should_reduce_manual_title_modification',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~1/'
					. 'characters_to_test_title_compression_algorithm_~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~1/'
					. 'characters_to_test_title_compression_algorithm_~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~2/'
					. 'characters_to_test_title_compression_algorithm_~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~2/'
					. 'characters_to_test_title_compression_algorithm_~1/'
					. 'which_should_reduce_manual_title_modification',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator but a very very very much longer subpage title'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~2/'
					. 'characters_to_test_title_compression_algorithm_~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator but a v~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator but a very very very much longer subpage title/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_~2/'
					. 'characters_to_test_title_compression_algorithm_~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator but a v~1/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression_algorithm'
			. '_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title_modification_after'
			. '_analyze_process_by_human_operator_because_this_would_take_a_lot_of_time_sometimes'
				=> 'A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression_'
					. 'algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
					. '_modification_after_analyze_process_by_human_operator_because_this_would_tak~1',
		];
	}
}
