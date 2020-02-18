<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;

class Compose extends CliCommandBase {
	protected function configure() {
		$this->setName( 'compose' );
		return parent::configure();
	}

	protected function makeFileList() {
		return [];
	}

	protected function processFiles() {
		$this->ensureTargetDirs();
		$workspace = new Workspace( new SplFileInfo( $this->src ) );
		$buckets = new DataBuckets( [
			'revision-contents',
			'title-attachments',
			'title-metadata',
			'title-revisions',
		] );
		$buckets->loadFromWorkspace( $workspace );
		$mediawikixmlbuilder = new Builder();
	}

	protected function doProcessFile() : bool {
		//Do nothing
		return true;
	}

	private function ensureTargetDirs() {
		$path = "{$this->dest}/result/images";
		if( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
	}
}
