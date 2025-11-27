<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

class ExecutionTime {
	/** @var float */
	private $executionTimeStart;

	/**
	 *
	 */
	public function __construct() {
		$this->executionTimeStart = $this->getMicrotime();
	}

	/**
	 * @return string
	 */
	public function getHumanReadableTime(): string {
		$executionTimeEnd = $this->getMicrotime();
		$executionTime = $executionTimeEnd - $this->executionTimeStart;

		$s = $executionTime % 60;
		$m = floor( ( $executionTime % 3600 ) / 60 );
		$h = floor( ( $executionTime % 86400 ) / 3600 );
		$d = floor( ( $executionTime % 2592000 ) / 86400 );

		$time = '';
		if ( $d > 1 ) {
			$time .= "{$d} days ";
		} elseif ( $d > 0 ) {
			$time .= "{$d} day ";
		}
		$time .= "{$h}h {$m}m {$s}s";

		return $time;
	}

	/**
	 * @return float
	 */
	private function getMicrotime(): float {
		return microtime( true );
	}
}
