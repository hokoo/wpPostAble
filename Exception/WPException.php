<?php
namespace iTRON\Exception;

use WP_Error;

interface WPException{
	public function getError(): WP_Error;
}
