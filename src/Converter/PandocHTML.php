<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Converter;

use HalloWelt\MediaWiki\Lib\Migration\ConverterBase;
use SplFileInfo;

class PandocHTML extends ConverterBase {

	protected function doConvert( SplFileInfo $file ): string {
		$path = $file->getPathname();
		$command = "pandoc -f html -t mediawiki $file";
		$escapedCommand = escapeshellcmd( $command );
		$wikitext = exec( $command );
		return $wikitext;
	}
}