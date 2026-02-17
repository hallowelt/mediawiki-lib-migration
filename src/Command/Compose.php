<?php

namespace HalloWelt\MediaWiki\Lib\Migration\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IComposer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;

class Compose extends CliCommandBase {
	protected function configure() {
		$this->setName( 'compose' );
		return parent::configure();
	}

	protected function makeFileList() {
		return [];
	}

	/**
	 *
	 * @return array
	 */
	protected function getBucketKeys() {
		return [
			'files',
			'revision-contents',
			'title-attachments',
			'title-metadata',
			'title-revisions',
		];
	}

	protected function processFiles(): int {
		$this->ensureTargetDirs();
		$this->workspace = new Workspace( new SplFileInfo( $this->src ) );

		$this->initExecutionTime();

		$this->buckets = new DataBuckets( $this->getBucketKeys() );
		$this->buckets->loadFromWorkspace( $this->workspace );
		$composers = $this->makeComposers();
		$mediawikixmlbuilder = new Builder();
		foreach ( $composers as $composer ) {
			$composer->buildXML( $mediawikixmlbuilder );
		}
		$mediawikixmlbuilder->buildAndSave( $this->dest . '/result/output.xml' );

		$this->logExecutionTime();

		return Command::SUCCESS;
	}

	/**
	 *
	 * @return IComposer[]
	 */
	protected function makeComposers() {
		$composers = [];
		$composerCallbacks = $this->config['composers'];
		foreach ( $composerCallbacks as $key => $callback ) {
			$composer = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
			);
			if ( $composer instanceof IComposer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IComposer object"
				);
			}
			if ( $composer instanceof IOutputAwareInterface ) {
				$composer->setOutput( $this->output );
			}
			$composers[] = $composer;
		}

		return $composers;
	}

	protected function doProcessFile(): bool {
		// Do nothing
		return true;
	}

	private function ensureTargetDirs() {
		$path = "{$this->dest}/result/images";
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
	}
}
