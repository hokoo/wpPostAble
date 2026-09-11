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

	public function testSerializedMetaIsUnserializedForReadsWithoutImplicitlyResavingIt(): void {
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
		self::assertArrayNotHasKey( 'meta_input', $saved[0] );
		self::assertSame( 'Saved title', $saved[0]['post_title'] );
		self::assertSame( 'private', $saved[0]['post_status'] );
		self::assertEquals( (object) [ 'theme' => 'тёмная' ], json_decode( $saved[0]['post_content_filtered'] )->display );
		self::assertTrue( $saved[1] );
	}

	public function testSaveIncludesOnlyExplicitlySetMetaAndClearsItAfterSuccess(): void {
		$this->stubLoadedPost(
			$this->post(),
			[
				'untouched' => [ 'first', 'second' ],
				'same'      => [ 'same value' ],
			]
		);
		$saved = [];
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $postData, bool $wpError ) use ( & $saved ): int {
				$saved[] = [ $postData, $wpError ];
				return $postData['ID'];
			}
		);

		$postable = new TestPostable( 'book', 23 );
		self::assertSame( 'first', $postable->getMetaField( 'untouched' ) );

		$postable
			->setMetaField( 'same', 'same value' )
			->setMetaField( 'nullable', null )
			->setMetaField( 'empty', '' );

		self::assertSame( $postable, $postable->savePost() );
		self::assertSame( $postable, $postable->savePost() );

		self::assertSame(
			[
				'same'     => 'same value',
				'nullable' => null,
				'empty'    => '',
			],
			$saved[0][0]['meta_input']
		);
		self::assertArrayNotHasKey( 'untouched', $saved[0][0]['meta_input'] );
		self::assertTrue( $saved[0][1] );
		self::assertArrayNotHasKey( 'meta_input', $saved[1][0] );
		self::assertTrue( $saved[1][1] );
		self::assertSame(
			[
				'untouched' => 'first',
				'same'      => 'same value',
				'nullable'  => null,
				'empty'     => '',
			],
			$postable->getMetaFields()
		);
	}

	/**
	 * @dataProvider failedSaveProvider
	 */
	public function testSaveFailurePreservesDirtyMetaForRetry( $result, string $message ): void {
		$postable = $this->loadPostable();
		$saved = [];
		$results = [ $result, 23, 23 ];
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $postData, bool $wpError ) use ( & $saved, & $results ) {
				$saved[] = [ $postData, $wpError ];
				return array_shift( $results );
			}
		);
		$postable->setMetaField( 'retry', [ 'attempt' => 1 ] );

		try {
			$postable->savePost();
			self::fail( 'Expected saving to fail.' );
		} catch ( wppaSavePostException $exception ) {
			self::assertSame( $postable, $exception->getPostable() );
			self::assertSame( $postable->getPost(), $exception->getPost() );
			self::assertInstanceOf( WP_Error::class, $exception->getError() );
			self::assertSame( $message, $exception->getMessage() );
			if ( $result instanceof WP_Error ) {
				self::assertSame( $result, $exception->getError() );
			} else {
				self::assertSame( 'save_post_failed', $exception->getError()->get_error_code() );
				self::assertSame( [ 'post_id' => 23 ], $exception->getError()->get_error_data() );
			}
		}

		self::assertSame( $postable, $postable->savePost() );
		self::assertSame( $postable, $postable->savePost() );

		self::assertSame( [ 'retry' => [ 'attempt' => 1 ] ], $saved[0][0]['meta_input'] );
		self::assertSame( [ 'retry' => [ 'attempt' => 1 ] ], $saved[1][0]['meta_input'] );
		self::assertArrayNotHasKey( 'fixture', $saved[0][0]['meta_input'] );
		self::assertArrayNotHasKey( 'fixture', $saved[1][0]['meta_input'] );
		self::assertArrayNotHasKey( 'meta_input', $saved[2][0] );
		self::assertTrue( $saved[0][1] );
		self::assertTrue( $saved[1][1] );
		self::assertTrue( $saved[2][1] );
	}

	public function failedSaveProvider(): array {
		$error = new WP_Error( 'save_failed', 'Could not save post.' );

		return [
			'WordPress error' => [ $error, 'Could not save post.' ],
			'zero result'     => [ 0, 'Unable to save post [ 23 ].' ],
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
