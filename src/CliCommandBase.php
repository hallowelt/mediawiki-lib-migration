<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use HalloWelt\MediaWiki\Lib\CommandLineTools\Commands\BatchFileProcessorBase;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use SplFileInfo;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;


abstract class CliCommandBase extends BatchFileProcessorBase {

	/**
	 *
	 * @var array
	 */
	protected $config = [];

	protected $workspace = null;

	public function __construct( $config ) {
		parent::__construct();
		$this->config = $config;
	}

	protected function processFiles() {
		$workspaceDir = new SplFileInfo( $this->dest );
		$this->workspace = new Workspace( $workspaceDir );
		return parent::processFiles();
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return boolean
	 */
	protected function processFile( SplFileInfo $file ): bool {
		//TODO: Ensure workspace dirs!?
		return $this->doProcessFile();
	}

	/**
	 * @return boolean
	 */
	protected abstract function doProcessFile(): bool;

	/**
	 *
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
	}
}