<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Converter;

use HalloWelt\MediaWiki\Lib\Migration\ConverterBase;
use SplFileInfo;

class PandocHTML extends ConverterBase {

	protected function doConvert( SplFileInfo $file ): string {
		$path = $file->getPathname();
		$command = "pandoc -f html -t mediawiki $path";
		$escapedCommand = escapeshellcmd( $command );
		$result = [];
		exec( $escapedCommand, $result );

		$wikitext = implode("\n", $result);

		return $wikitext;
	}
}
