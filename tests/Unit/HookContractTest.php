<?php

namespace iTRON\wpPostAble\Tests\Unit;

use Brain\Monkey\Functions;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use WP_Post;

final class HookContractTest extends WordPressTestCase {
	public function testCreationAndLoadingHooksKeepTheirNamesAndArguments(): void {
		$post = new WP_Post(
			[
				'ID'        => 31,
				'post_type' => 'book',
			]
		);
		Functions\when( 'wp_insert_post' )->justReturn( 31 );
		$this->stubLoadedPost( $post );

		$postable = new TestPostable( 'book' );
		$class = TestPostable::class;

		self::assertSame(
			[
				[ '\\wpPostAbleTrait\\init\\defaultStatus', [ 'draft', $class ] ],
				[ $class . '\\wpPostAbleTrait\\init\\defaultStatus', [ 'draft', 'draft', $class ] ],
				[ '\\wpPostAbleTrait\\init\\defaultTitle', [ 'draft', $class ] ],
				[ $class . '\\wpPostAbleTrait\\init\\defaultTitle', [ 'draft', 'draft', $class ] ],
				[ '\\wpPostAbleTrait\\init\\defaultContent', [ 'Empty.', $class ] ],
				[ $class . '\\wpPostAbleTrait\\init\\defaultContent', [ 'Empty.', 'Empty.', $class ] ],
				[ '\\wpPostAbleTrait\\loadPost\\equalPostType', [ true, $class ] ],
				[ $class . '\\wpPostAbleTrait\\loadPost\\equalPostType', [ true, true, $class ] ],
				[ '\\wpPostAbleTrait\\loadPost\\loadMeta', [ true, $postable, $class ] ],
				[ $class . '\\wpPostAbleTrait\\loadPost\\loadMeta', [ true, true, $postable, $class ] ],
			],
			$this->filterCalls
		);
		self::assertSame(
			[
				[ '\\wpPostAbleTrait\\loadPost\\loading', [ $postable, $class ] ],
				[ $class . '\\wpPostAbleTrait\\loadPost\\loading', [ $postable, $class ] ],
			],
			$this->actionCalls
		);
	}

	public function testLoadMetaFilterCanDisableMetadataRead(): void {
		$post = new WP_Post(
			[
				'ID'        => 32,
				'post_type' => 'book',
			]
		);
		$this->filterOverrides['\\wpPostAbleTrait\\loadPost\\loadMeta'] = static function (): bool {
			return false;
		};
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\expect( 'get_post_meta' )->never();

		$postable = new TestPostable( 'book', 32 );

		self::assertSame( [], $postable->getMetaFields() );
	}
}
