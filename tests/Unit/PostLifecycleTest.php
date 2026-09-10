<?php

namespace iTRON\wpPostAble\Tests\Unit;

use Brain\Monkey\Functions;
use iTRON\wpPostAble\Exceptions\wppaCreatePostException;
use iTRON\wpPostAble\Exceptions\wppaLoadPostException;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use WP_Error;
use WP_Post;

final class PostLifecycleTest extends WordPressTestCase {
	public function testItCreatesPostWithFilteredDefaultsAndLoadsIt(): void {
		$createdPost = new WP_Post(
			[
				'ID'        => 101,
				'post_type' => 'book',
			]
		);
		$inserted = null;

		$this->filterOverrides['\\wpPostAbleTrait\\init\\defaultStatus'] = static function (): string {
			return 'pending';
		};
		$this->filterOverrides['\\wpPostAbleTrait\\init\\defaultTitle'] = static function (): string {
			return 'New book';
		};
		$this->filterOverrides['\\wpPostAbleTrait\\init\\defaultContent'] = static function (): string {
			return 'New content';
		};

		Functions\when( 'wp_insert_post' )->alias(
			static function ( array $postData, bool $wpError ) use ( & $inserted ): int {
				$inserted = [ $postData, $wpError ];
				return 101;
			}
		);
		$this->stubLoadedPost( $createdPost );

		$postable = new TestPostable( 'book' );

		self::assertSame(
			[
				[
					'post_type'    => 'book',
					'post_status'  => 'pending',
					'post_title'   => 'New book',
					'post_content' => 'New content',
				],
				true,
			],
			$inserted
		);
		self::assertSame( 'book', $postable->getPostType() );
		self::assertSame( $createdPost, $postable->getPost() );
	}

	public function testItLoadsExistingPostWithoutCreatingOne(): void {
		$post = new WP_Post(
			[
				'ID'        => 17,
				'post_type' => 'book',
			]
		);

		Functions\expect( 'wp_insert_post' )->never();
		$this->stubLoadedPost( $post );

		$postable = new TestPostable( 'book', 17 );

		self::assertSame( $post, $postable->getPost() );
	}

	/**
	 * @dataProvider invalidCreatedPostProvider
	 */
	public function testCreateFailurePreservesPostableAndWordPressError( $result, string $message ): void {
		Functions\when( 'wp_insert_post' )->justReturn( $result );

		try {
			new TestPostable( 'book' );
			self::fail( 'Expected creation to fail.' );
		} catch ( wppaCreatePostException $exception ) {
			self::assertInstanceOf( TestPostable::class, $exception->getPostable() );
			self::assertInstanceOf( WP_Error::class, $exception->getError() );
			self::assertSame( $message, $exception->getMessage() );
		}
	}

	public function invalidCreatedPostProvider(): array {
		$error = new WP_Error( 'create_failed', 'Could not create post.' );

		return [
			'WordPress error' => [ $error, 'Could not create post.' ],
			'zero result'     => [ 0, '' ],
		];
	}

	public function testMissingPostFailurePreservesIdAndPostable(): void {
		Functions\when( 'get_post' )->justReturn( null );

		try {
			new TestPostable( 'book', 404 );
			self::fail( 'Expected loading to fail.' );
		} catch ( wppaLoadPostException $exception ) {
			self::assertSame( 404, $exception->getPostID() );
			self::assertInstanceOf( TestPostable::class, $exception->getPostable() );
			self::assertStringContainsString( 'Incorrect post id', $exception->getMessage() );
		}
	}

	public function testIncompatiblePostTypeFailsWithPreservedContext(): void {
		$post = new WP_Post(
			[
				'ID'        => 29,
				'post_type' => 'page',
			]
		);
		Functions\when( 'get_post' )->justReturn( $post );

		try {
			new TestPostable( 'book', 29 );
			self::fail( 'Expected loading to fail.' );
		} catch ( wppaLoadPostException $exception ) {
			self::assertSame( 29, $exception->getPostID() );
			self::assertSame( 'book', $exception->getPostable()->getPostType() );
			self::assertStringContainsString( 'trying to load "page"', $exception->getMessage() );
		}
	}
}
