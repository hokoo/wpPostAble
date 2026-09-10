<?php

namespace iTRON\wpPostAble\Tests\Unit;

use Brain\Monkey\Functions;
use iTRON\wpPostAble\Exceptions\wppaDeletePostException;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use ReflectionProperty;
use WP_Error;
use WP_Post;

final class DeletePostTest extends WordPressTestCase {
	public function testSuccessfulDeletionKeepsOriginalHookContextAndClearsPostAfterCoreSuccess(): void {
		$post = $this->post();
		$this->stubLoadedPost( $post );
		$postable = new TestPostable( 'book', $post->ID );
		$this->actionCalls = [];

		Functions\expect( 'wp_delete_post' )
			->once()
			->with( $post->ID )
			->andReturn( $post );

		self::assertNull( $postable->deletePost() );

		$class = TestPostable::class;
		self::assertSame(
			[
				[ '\\wpPostAbleTrait\\deletePost\\beforeDeletePost', [ $post->ID, $post, $class ] ],
				[ $class . '\\wpPostAbleTrait\\deletePost\\beforeDeletePost', [ $post->ID, $post, $class ] ],
				[ '\\wpPostAbleTrait\\deletePost\\afterDeletePost', [ $post->ID, $post, $class ] ],
				[ $class . '\\wpPostAbleTrait\\deletePost\\afterDeletePost', [ $post->ID, $post, $class ] ],
			],
			$this->actionCalls
		);
		self::assertNull( $this->readInternalPost( $postable ) );
	}

	/**
	 * @dataProvider failedDeleteProvider
	 */
	public function testFailedDeletionPreservesPostAndThrowsDedicatedException( $result ): void {
		$post = $this->post();
		$this->stubLoadedPost( $post );
		$postable = new TestPostable( 'book', $post->ID );
		$this->actionCalls = [];

		Functions\expect( 'wp_delete_post' )
			->once()
			->with( $post->ID )
			->andReturn( $result );

		try {
			$postable->deletePost();
			self::fail( 'Expected deletion to fail.' );
		} catch ( wppaDeletePostException $exception ) {
			self::assertSame( $postable, $exception->getPostable() );
			self::assertSame( $post, $exception->getPost() );
			self::assertInstanceOf( WP_Error::class, $exception->getError() );
			self::assertNotEmpty( $exception->getError()->get_error_code() );
			self::assertNotEmpty( $exception->getError()->get_error_message() );
			self::assertNotEmpty( $exception->getMessage() );
		}

		self::assertSame( $post, $postable->getPost() );
		$class = TestPostable::class;
		self::assertSame(
			[
				[ '\\wpPostAbleTrait\\deletePost\\beforeDeletePost', [ $post->ID, $post, $class ] ],
				[ $class . '\\wpPostAbleTrait\\deletePost\\beforeDeletePost', [ $post->ID, $post, $class ] ],
			],
			$this->actionCalls
		);
	}

	public function failedDeleteProvider(): array {
		return [
			'false result' => [ false ],
			'null result'  => [ null ],
		];
	}

	private function post(): WP_Post {
		return new WP_Post(
			[
				'ID'        => 73,
				'post_type' => 'book',
			]
		);
	}

	private function readInternalPost( TestPostable $postable ) {
		$property = new ReflectionProperty( TestPostable::class, 'post' );
		$property->setAccessible( true );

		return $property->getValue( $postable );
	}
}
