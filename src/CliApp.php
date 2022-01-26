<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Symfony\Component\Console\Application;

class CliApp extends Application {

	/**
	 *
	 * @param string $config
	 */
	public function __construct( $config ) {
		parent::__construct( 'Migrate' );

		$this->add( new Command\Analyze( $config ) );
		$this->add( new Command\Extract( $config ) );
		$this->add( new Command\Convert( $config ) );
		$this->add( new Command\Compose( $config ) );
	}

}
