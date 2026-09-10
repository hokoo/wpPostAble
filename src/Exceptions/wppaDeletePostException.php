<?php

namespace iTRON\wpPostAble\Exceptions;

use iTRON\wpPostAble\wpPostAble;
use Throwable;
use WP_Error;
use WP_Post;

class wppaDeletePostException extends wppaException implements wpException {
	public $post;
	public $error;

	function __construct(
		wpPostAble $postable,
		WP_Post $post,
		WP_Error $error,
		$message = "",
		$code = 0,
		?Throwable $previous = null
	) {
		parent::__construct( $postable, $message, $code, $previous );
		$this->post = $post;
		$this->error = $error;
	}

	function getPost(): WP_Post {
		return $this->post;
	}

	function getError(): WP_Error {
		return $this->error;
	}
}
