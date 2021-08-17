<?php
namespace iTRON\Exception;

use iTRON\WPPostAble;
use Throwable;

class WPPATException extends \Exception {

	public $postable;

	function __construct( WPPostAble $postable, $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->postable = $postable;
	}

	function getPostable(): WPPostAble {
		return $this->postable;
	}
}