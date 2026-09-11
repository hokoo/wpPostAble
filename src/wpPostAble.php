<?php
/**
 * Use this interface in conjunction wpPostAbleTrait only.
 */

namespace iTRON\wpPostAble;

use WP_Post;

interface wpPostAble{
	public function getPost(): WP_Post;
	public function savePost(): self;
	public function deletePost(): void;
	public function getPostType(): string;
	public function getTitle(): string;
	public function setTitle( string $title ): self;
	public function getSlug(): string;
	public function setSlug( string $slug ): self;
	public function getMenuOrder(): int;
	public function setMenuOrder( int $menuOrder ): self;
	public function getStatus(): string;
	public function setStatus( string $status ): self;
	public function setMetaField( string $meta_key, $meta_value ): self;
	public function getMetaField( string $meta_key );
	public function getMetaFields(): array;
	public function getParam( string $param );
	public function setParam( string $param, $value ): void;
	public function publish(): self;
	public function draft(): self;
}
