<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Converter;

use HalloWelt\MediaWiki\Lib\Migration\ConverterBase;
use SplFileInfo;

class PandocHTML extends ConverterBase {

	/**
	 * @inheritDoc
	 */
	protected function doConvert( SplFileInfo $file ): string {
		$path = $file->getPathname();
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.escapeshellarg
		$escapedCommand = 'pandoc -f html -t mediawiki ' . escapeshellarg( $path );
		$output = [];
		$resultCode = null;
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.exec
		exec( $escapedCommand, $output, $resultCode );

		if ( $result !== 0 ) {
			// pandoc error
			return "<!-- html could not be converted to wikitext -->";
		}

		$wikitext = implode( "\n", $output );

		return $wikitext;
	}
}
