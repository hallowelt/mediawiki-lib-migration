<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Symfony\Component\Console\Application;

class CliApp extends Application {

	public function __construct( $config ) {
		parent::__construct( 'Migrate' );

		$this->addCommandAnalyze( $config );
		$this->addCommandExtract( $config );
		$this->addCommandConvert( $config );
		$this->addCommandCompose( $config );
	}

	/**
	 * @param array $config
	 */
	private function addCommandAnalyze( $config ): void {
		if ( $this->hasOverride( 'analyze', $config ) ) {
			$this->addCommandOverride( 'analyze', $config );
		}
		else {
			$this->add( new Command\Analyze( $config ) );
		}
	}

	/**
	 * @param array $config
	 */
	private function addCommandExtract( $config ): void {
		if ( $this->hasOverride( 'extract', $config ) ) {
			$this->addCommandOverride( 'extract', $config );
		}
		else {
			$this->add( new Command\Extract( $config ) );
		}
	}

	/**
	 * @param array $config
	 */
	private function addCommandConvert( $config ): void {
		if ( $this->hasOverride( 'convert', $config ) ) {
			$this->addCommandOverride( 'convert', $config );
		}
		else {
			$this->add( new Command\Convert( $config ) );
		}
	}

	/**
	 * @param array $config
	 */
	private function addCommandCompose( $config ): void {
		if ( $this->hasOverride( 'compose', $config ) ) {
			$this->addCommandOverride( 'compose', $config );
		}
		else {
			$this->add( new Command\Compose( $config ) );
		}
	}

	/**
	 * @param string $command
	 * @param array $config
	 * @return void
	 */
	private function addCommandOverride( $command, $config ): void {
		$command = call_user_func_array(
			$config['command-overrides'][$command]['factory'],
			[ $this->config ]
		);
		$this->add( $command );
	}

	/**
	 * @param string $command
	 * @param array $config
	 * @return boolean
	 */
	private function hasOverride( $command, $config): bool {
		if ( isset( $config['command-overrides'][$command]['factory'] ) ) {
			return true;
		}
		return false;
	}
}