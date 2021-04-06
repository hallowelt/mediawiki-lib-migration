<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Symfony\Component\Console\Output\Output;

interface IOutputAwareInterface {

	public function setOutput( Output $output );
}
