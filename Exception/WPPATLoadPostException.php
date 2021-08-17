<?php
namespace iTRON\Exception;

use iTRON\WPPostAble;
use Throwable;

class WPPATLoadPostException extends WPPATException {
	public $post_id;

	function __construct( $post_id, WPPostAble $postable, $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $postable, $message, $code, $previous );
		$this->post_id = $post_id;
	}

	function getPostID(){
		return $this->post_id;
	}
}
