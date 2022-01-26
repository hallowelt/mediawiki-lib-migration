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
		$command = "pandoc -f html -t mediawiki $path";
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.escapeshellcmd
		$escapedCommand = escapeshellcmd( $command );
		$result = [];
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.exec
		exec( $escapedCommand, $result );

		$wikitext = implode( "\n", $result );

		return $wikitext;
	}
}
