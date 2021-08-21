<?php
namespace iTRON\wpPostAble\Exception;

use WP_Error;

interface wpException{
	public function getError(): WP_Error;
}
