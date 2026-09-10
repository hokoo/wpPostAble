<?php

namespace iTRON\wpPostAble\Tests\Unit;

use Brain\Monkey\Functions;
use iTRON\wpPostAble\Exceptions\wppaSavePostException;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use WP_Error;
use WP_Post;

final class PostMutationTest extends WordPressTestCase {
	public function testTitleStatusAndMetaMutatorsAreFluent(): void {
		$postable = $this->loadPostable();

		self::assertSame( $postable, $postable->setTitle( 'Changed title' ) );
		self::assertSame( $postable, $postable->setStatus( 'private' ) );
		self::assertSame( $postable, $postable->setMetaField( 'rating', 5 ) );
		self::assertSame( 'Changed title', $postable->getTitle() );
		self::assertSame( 'private', $postable->getStatus() );
		self::assertSame( 5, $postable->getMetaField( 'rating' ) );
		self::assertNull( $postable->getMetaField( 'missing' ) );
		self::assertSame( [ 'fixture' => 'value', 'rating' => 5 ], $postable->getMetaFields() );
	}

	public function testSerializedMetaIsUnserializedExactlyOnceBeforeSave(): void {
		$post = $this->post();
		$this->stubLoadedPost(
			$post,
			[
				'settings' => [ serialize( [ 'layout' => 'grid', 'columns' => 3 ] ) ],
			]
		);
		$saved = null;
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $postData, bool $wpError ) use ( & $saved ): int {
				$saved = [ $postData, $wpError ];
				return 23;
			}
		);

		$postable = new TestPostable( 'book', 23 );
		$postable->setTitle( 'Saved title' );
		$postable->setStatus( 'private' );
		$postable->setParam( 'display', [ 'theme' => 'тёмная' ] );
		$result = $postable->savePost();

		self::assertSame( $postable, $result );
		self::assertSame( [ 'layout' => 'grid', 'columns' => 3 ], $postable->getMetaField( 'settings' ) );
		self::assertSame( [ 'layout' => 'grid', 'columns' => 3 ], $saved[0]['meta_input']['settings'] );
		self::assertSame( 'Saved title', $saved[0]['post_title'] );
		self::assertSame( 'private', $saved[0]['post_status'] );
		self::assertEquals( (object) [ 'theme' => 'тёмная' ], json_decode( $saved[0]['post_content_filtered'] )->display );
		self::assertTrue( $saved[1] );
	}

	/**
	 * @dataProvider failedSaveProvider
	 */
	public function testSaveFailurePreservesPostAndWordPressError( $result, string $message ): void {
		$postable = $this->loadPostable();
		Functions\when( 'wp_update_post' )->justReturn( $result );

		try {
			$postable->savePost();
			self::fail( 'Expected saving to fail.' );
		} catch ( wppaSavePostException $exception ) {
			self::assertSame( $postable, $exception->getPostable() );
			self::assertSame( $postable->getPost(), $exception->getPost() );
			self::assertInstanceOf( WP_Error::class, $exception->getError() );
			self::assertSame( $message, $exception->getMessage() );
		}
	}

	public function failedSaveProvider(): array {
		$error = new WP_Error( 'save_failed', 'Could not save post.' );

		return [
			'WordPress error' => [ $error, 'Could not save post.' ],
			'zero result'     => [ 0, '' ],
		];
	}

	public function testPublishAndDraftSaveTheirNewStatuses(): void {
		$postable = $this->loadPostable();
		$statuses = [];
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $postData ) use ( & $statuses ): int {
				$statuses[] = $postData['post_status'];
				return $postData['ID'];
			}
		);

		self::assertSame( $postable, $postable->publish() );
		self::assertSame( $postable, $postable->draft() );

		self::assertSame( [ 'publish', 'draft' ], $statuses );
		self::assertSame( 'draft', $postable->getStatus() );
	}

	public function testParametersRoundTripEmptyUnicodeAndNestedValues(): void {
		$postable = $this->loadPostable();

		$postable->setParam( 'empty', '' );
		$postable->setParam( 'unicode', 'Привет, мир' );
		$postable->setParam( 'nested', [ 'enabled' => true, 'items' => [ 1, 2, 3 ] ] );

		self::assertSame( '', $postable->getParam( 'empty' ) );
		self::assertSame( 'Привет, мир', $postable->getParam( 'unicode' ) );
		self::assertEquals(
			(object) [ 'enabled' => true, 'items' => [ 1, 2, 3 ] ],
			$postable->getParam( 'nested' )
		);
		self::assertNull( $postable->getParam( 'missing' ) );
		self::assertStringContainsString( 'Привет, мир', $postable->getPost()->post_content_filtered );
	}

	private function loadPostable(): TestPostable {
		$this->stubLoadedPost( $this->post() );
		return new TestPostable( 'book', 23 );
	}

	private function post(): WP_Post {
		return new WP_Post(
			[
				'ID'                    => 23,
				'post_type'             => 'book',
				'post_title'            => 'Original title',
				'post_status'           => 'draft',
				'post_content_filtered' => '',
			]
		);
	}
}
