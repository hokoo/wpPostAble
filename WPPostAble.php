<?php
/**
 * Use this interface in conjunction WPPostAbleTrait only.
 */
namespace iTRON;

use WP_Post;

interface WPPostAble{
	public function getPost(): WP_Post;
	public function savePost();
	public function loadPost( int $post_id );
	public function getPostType();
	public function getTitle();
	public function setTitle( string $title );
	public function getStatus();
	public function setStatus( string $status );
	public function publish();
	public function draft();
}
