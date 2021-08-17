<?php
namespace iTRON\Exception;

use WP_Error;

interface wpException{
	public function getError(): WP_Error;
}
