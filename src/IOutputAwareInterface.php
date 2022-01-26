<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Symfony\Component\Console\Output\Output;

interface IOutputAwareInterface {

	/**
	 *
	 * @param Output $output
	 * @return void
	 */
	public function setOutput( Output $output );
}
