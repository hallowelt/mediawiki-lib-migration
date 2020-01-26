<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;

class Convert extends CliCommandBase {
	protected function configure() {
		$this->setName( 'convert' );
		return parent::configure();
	}

	protected function doProcessFile(): bool {
		return true;
	}
}
