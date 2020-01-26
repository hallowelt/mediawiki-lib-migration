<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;

class Extract extends CliCommandBase {
	protected function configure() {
		$this->setName( 'extract' );
		return parent::configure();
	}

	protected function doProcessFile(): bool {
		return true;
	}
}
