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
		$result = [];
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.exec
		exec( $escapedCommand, $result );

		$wikitext = implode( "\n", $result );

		return $wikitext;
	}
}
