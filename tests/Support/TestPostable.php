<?php

namespace iTRON\wpPostAble\Tests\Support;

use iTRON\wpPostAble\wpPostAble;
use iTRON\wpPostAble\wpPostAbleTrait;

final class TestPostable implements wpPostAble {
	use wpPostAbleTrait;

	public function __construct( string $postType = 'book', int $postId = 0 ) {
		$this->wpPostAble( $postType, $postId );
	}
}
