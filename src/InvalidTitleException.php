<?php

namespace HalloWelt\MediaWiki\Lib\Migration;

use Exception;
use Throwable;

class InvalidTitleException extends Exception {

	/**
	 * @var string
	 */
	private $invalidTitle = '';

	/**
	 * @param string $invalidTitle
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct( $invalidTitle, $message = "", $code = 0, ?Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->invalidTitle = $invalidTitle;
	}

	/**
	 * @return string
	 */
	public function getInvalidTitle() {
		return $this->invalidTitle;
	}
}
