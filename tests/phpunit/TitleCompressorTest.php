<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Tests;

use HalloWelt\MediaWiki\Lib\Migration\TitleCompressor;
use PHPUnit\Framework\TestCase;

class TitleCompressorTest extends TestCase {

	/**
	 * @covers HalloWelt\MediaWiki\Lib\Migration\TitleCompressor::build
	 * @return void
	 */
	public function testExecute() {
		$titleCompressor = new TitleCompressor();

		$pagesTitlesMap = $this->getPagesTitlesMap();
		$compressedTitles = $titleCompressor->execute( $pagesTitlesMap );

		$this->assertEquals(
			$this->getExpectedCompressedTitleMap(),
			$compressedTitles
		);
	}

	private function getPagesTitlesMap(): array {
		return [
			'123456701---long root'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
					. '_modification_after_analyze_process_by_human_operator_because_this_would_take_a_lot_of_time'
					.'_sometimes',
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
		];
	}

	private function getExpectedCompressedTitleMap(): array {
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
					. '_TitleCompressor.php_which_should_reduc~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
			. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
			. '_modification_after_analyze_process_by_human_operator'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
			. '_algorithm_of_mediawiki-lib-migration_TitleCompressor.php_which_should_reduce_manual_title'
			. '_modification_after_analyze_process_by_human_operator/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255_characters_to_test_title_compression'
					. '_algorithm_of_mediawiki-lib-migration~1/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
				. 'characters_to_test_title_compression_algorithm_of~1/'
				. 'which_should_reduce_manual_title_modification',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~1/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator/'
					. 'because_this_would_take_a_lot_of_time_sometimes',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~2',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~2/'
					. 'which_should_reduce_manual_title_modification',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator but a very very very much longer subpage title'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~2/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator but a ver~1',
			'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
			. 'characters_to_test_title_compression_algorithm_of_mediawiki-lib-migration_TitleCompressor.php part 2/'
			. 'which_should_reduce_manual_title_modification/'
			. 'after_analyze_process_by_human_operator but a very very very much longer subpage title/'
			. 'because_this_would_take_a_lot_of_time_sometimes'
				=> 'ABC:A_very_very_very_long_root_page_title_exeeding_255/'
					. 'characters_to_test_title_compression_algorithm_of~2/'
					. 'which_should_reduce_manual_title_modification/'
					. 'after_analyze_process_by_human_operator but a ver~1/'
					. 'because_this_would_take_a_lot_of_time_sometimes',

		];
	}
}
