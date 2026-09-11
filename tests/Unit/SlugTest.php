<?php

namespace iTRON\wpPostAble\Tests\Unit;

use Brain\Monkey\Functions;
use iTRON\wpPostAble\Exceptions\wppaSavePostException;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use iTRON\wpPostAble\wpPostAble;
use ReflectionMethod;
use WP_Error;
use WP_Post;

final class SlugTest extends WordPressTestCase {
	public function testGetSlugReadsTheLoadedPostName(): void {
		$postable = $this->loadPostable( 'existing-slug' );

		self::assertSame( 'existing-slug', $postable->getSlug() );
	}

	public function testSetSlugChangesOnlyTheInMemoryPostAndIsChainable(): void {
		$postable = $this->loadPostable( 'existing-slug' );
		$before = get_object_vars( $postable->getPost() );

		Functions\expect( 'wp_update_post' )->never();

		self::assertSame( $postable, $postable->setSlug( 'Requested Slug' ) );
		self::assertSame( 'Requested Slug', $postable->getSlug() );

		$expected = $before;
		$expected['post_name'] = 'Requested Slug';
		self::assertSame( $expected, get_object_vars( $postable->getPost() ) );
	}

	public function testSavePassesTheExactSlugThroughTheExistingPostLifecycle(): void {
		$postable = $this->loadPostable( 'existing-slug' );
		$saved = null;
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $post_data, bool $wp_error ) use ( & $saved ): int {
				$saved = [ $post_data, $wp_error ];
				return $post_data['ID'];
			}
		);

		$postable->setSlug( 'Requested Slug' );
		self::assertSame( $postable, $postable->savePost() );

		self::assertSame( get_object_vars( $postable->getPost() ), $saved[0] );
		self::assertSame( 'Requested Slug', $saved[0]['post_name'] );
		self::assertTrue( $saved[1] );
	}

	/**
	 * @dataProvider failedSaveProvider
	 */
	public function testFailedSaveKeepsTheRequestedSlugForRetry( $failure, string $message ): void {
		$postable = $this->loadPostable( 'existing-slug' );
		$post = $postable->getPost();
		$saved = [];
		$results = [ $failure, 23 ];
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $post_data, bool $wp_error ) use ( & $saved, & $results ) {
				$saved[] = [ $post_data, $wp_error ];
				return array_shift( $results );
			}
		);

		$postable->setSlug( 'Retry Slug' );

		try {
			$postable->savePost();
			self::fail( 'Expected saving to fail.' );
		} catch ( wppaSavePostException $exception ) {
			self::assertSame( $postable, $exception->getPostable() );
			self::assertSame( $post, $exception->getPost() );
			self::assertInstanceOf( WP_Error::class, $exception->getError() );
			self::assertSame( $message, $exception->getMessage() );
		}

		self::assertSame( $post, $postable->getPost() );
		self::assertSame( 'Retry Slug', $postable->getSlug() );
		self::assertSame( 'Retry Slug', $saved[0][0]['post_name'] );
		self::assertTrue( $saved[0][1] );

		self::assertSame( $postable, $postable->savePost() );
		self::assertSame( 'Retry Slug', $postable->getSlug() );
		self::assertSame( 'Retry Slug', $saved[1][0]['post_name'] );
		self::assertTrue( $saved[1][1] );
	}

	public function failedSaveProvider(): array {
		return [
			'WordPress error' => [ new WP_Error( 'save_failed', 'Could not save post.' ), 'Could not save post.' ],
			'zero result'     => [ 0, '' ],
		];
	}

	public function testInterfaceDeclaresTheStableSlugSignatures(): void {
		$get_slug = new ReflectionMethod( wpPostAble::class, 'getSlug' );
		$set_slug = new ReflectionMethod( wpPostAble::class, 'setSlug' );
		$parameter = $set_slug->getParameters()[0];

		self::assertSame( 'string', $get_slug->getReturnType()->getName() );
		self::assertFalse( $get_slug->getReturnType()->allowsNull() );
		self::assertSame( 'slug', $parameter->getName() );
		self::assertSame( 'string', $parameter->getType()->getName() );
		self::assertFalse( $parameter->getType()->allowsNull() );
		self::assertSame( 'self', $set_slug->getReturnType()->getName() );
		self::assertFalse( $set_slug->getReturnType()->allowsNull() );
	}

	private function loadPostable( string $slug ): TestPostable {
		$post = new WP_Post(
			[
				'ID'                    => 23,
				'post_type'             => 'book',
				'post_title'            => 'Original title',
				'post_name'             => $slug,
				'post_status'           => 'draft',
				'post_content'          => 'Original content',
				'post_content_filtered' => '{}',
			]
		);
		$this->stubLoadedPost( $post );

		return new TestPostable( 'book', 23 );
	}
}
