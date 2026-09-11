<?php

namespace iTRON\wpPostAble\Tests\Support;

use iTRON\wpPostAble\wpPostAble;
use iTRON\wpPostAble\wpPostAbleTrait;
use WP_Post;

final class TestPostable implements wpPostAble {
	use wpPostAbleTrait;

	/**
	 * @param int|WP_Post|null $postId Existing post or ID, or null/zero to create one.
	 */
	public function __construct( string $postType = 'book', $postId = 0 ) {
		$this->wpPostAble( $postType, $postId );
	}
}
