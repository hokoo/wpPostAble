<?php


namespace iTRON\Exception;

use iTRON\WPPostAble;
use Throwable;
use WP_Error;

class WPPATCreatePostException extends WPPATException implements WPException {

	public $error;

	function __construct( WPPostAble $postable, WP_Error $error, $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $postable, $message, $code, $previous );
		$this->error = $error;
	}

	function getError(): WP_Error {
		return $this->error;
	}
}
