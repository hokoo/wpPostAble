<?php

namespace iTRON\wpPostAble\Tests\Support;

use iTRON\wpPostAble\wpPostAble;
use WP_Post;

/**
 * Deliberately implements the interface without wpPostAbleTrait.
 *
 * Loading this fixture proves that the frozen interface can be implemented on
 * every PHP version in the supported CI matrix.
 */
final class ManualPostable implements wpPostAble {
	private $post;
	private $post_type;
	private $post_meta = [];
	private $params = [];

	public function __construct( WP_Post $post, string $post_type = 'book' ) {
		$this->post = $post;
		$this->post_type = $post_type;
	}

	public function getPost(): WP_Post {
		return $this->post;
	}

	public function savePost(): self {
		return $this;
	}

	public function deletePost(): void {
	}

	public function getPostType(): string {
		return $this->post_type;
	}

	public function getTitle(): string {
		return $this->post->post_title;
	}

	public function setTitle( string $title ): self {
		$this->post->post_title = $title;
		return $this;
	}

	public function getSlug(): string {
		return $this->post->post_name;
	}

	public function setSlug( string $slug ): self {
		$this->post->post_name = $slug;
		return $this;
	}

	public function getMenuOrder(): int {
		return (int) $this->post->menu_order;
	}

	public function setMenuOrder( int $menuOrder ): self {
		$this->post->menu_order = $menuOrder;
		return $this;
	}

	public function getStatus(): string {
		return $this->post->post_status;
	}

	public function setStatus( string $status ): self {
		$this->post->post_status = $status;
		return $this;
	}

	public function setMetaField( string $meta_key, $meta_value ): self {
		$this->post_meta[ $meta_key ] = $meta_value;
		return $this;
	}

	public function getMetaField( string $meta_key ) {
		return $this->post_meta[ $meta_key ] ?? null;
	}

	public function getMetaFields(): array {
		return $this->post_meta;
	}

	public function getParam( string $param ) {
		return $this->params[ $param ] ?? null;
	}

	public function setParam( string $param, $value ): void {
		$this->params[ $param ] = $value;
	}

	public function publish(): self {
		return $this->setStatus( 'publish' );
	}

	public function draft(): self {
		return $this->setStatus( 'draft' );
	}
}
