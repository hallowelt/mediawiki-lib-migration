<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;

class Compose extends CliCommandBase {
	protected function configure() {
		$this->setName( 'compose' );
		return parent::configure();
	}

	protected function doProcessFile(): bool {
		return true;
	}
}
