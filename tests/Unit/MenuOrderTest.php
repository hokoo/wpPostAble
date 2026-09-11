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

final class MenuOrderTest extends WordPressTestCase {
	public function testGetMenuOrderReadsTheLoadedPostValue(): void {
		$postable = $this->loadPostable( 9 );

		self::assertSame( 9, $postable->getMenuOrder() );
	}

	/**
	 * @dataProvider menuOrderProvider
	 */
	public function testSetMenuOrderChangesOnlyTheInMemoryPostAndIsChainable( int $menu_order ): void {
		$postable = $this->loadPostable( 9 );
		$before = get_object_vars( $postable->getPost() );

		Functions\expect( 'wp_update_post' )->never();

		self::assertSame( $postable, $postable->setMenuOrder( $menu_order ) );
		self::assertSame( $menu_order, $postable->getMenuOrder() );

		$expected = $before;
		$expected['menu_order'] = $menu_order;
		self::assertSame( $expected, get_object_vars( $postable->getPost() ) );
	}

	public function menuOrderProvider(): array {
		return [
			'zero'     => [ 0 ],
			'positive' => [ 17 ],
			'negative' => [ -7 ],
		];
	}

	public function testSavePassesTheExactMenuOrderThroughTheExistingPostLifecycle(): void {
		$postable = $this->loadPostable( 9 );
		$saved = null;
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $post_data, bool $wp_error ) use ( &$saved ): int {
				$saved = [ $post_data, $wp_error ];
				return $post_data['ID'];
			}
		);

		$postable->setMenuOrder( -7 );
		self::assertSame( $postable, $postable->savePost() );

		self::assertSame( get_object_vars( $postable->getPost() ), $saved[0] );
		self::assertSame( -7, $saved[0]['menu_order'] );
		self::assertTrue( $saved[1] );
	}

	/**
	 * @dataProvider failedSaveProvider
	 */
	public function testFailedSaveKeepsTheRequestedMenuOrderForRetry( $failure, string $message ): void {
		$postable = $this->loadPostable( 9 );
		$post = $postable->getPost();
		$saved = [];
		$results = [ $failure, 23 ];
		Functions\when( 'wp_update_post' )->alias(
			static function ( array $post_data, bool $wp_error ) use ( &$saved, &$results ) {
				$saved[] = [ $post_data, $wp_error ];
				return array_shift( $results );
			}
		);

		$postable->setMenuOrder( -7 );

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
		self::assertSame( -7, $postable->getMenuOrder() );
		self::assertSame( -7, $saved[0][0]['menu_order'] );
		self::assertTrue( $saved[0][1] );

		self::assertSame( $postable, $postable->savePost() );
		self::assertSame( -7, $postable->getMenuOrder() );
		self::assertSame( -7, $saved[1][0]['menu_order'] );
		self::assertTrue( $saved[1][1] );
	}

	public function failedSaveProvider(): array {
		return [
			'WordPress error' => [ new WP_Error( 'save_failed', 'Could not save post.' ), 'Could not save post.' ],
			'zero result'     => [ 0, 'Unable to save post [ 23 ].' ],
		];
	}

	public function testInterfaceDeclaresTheStableMenuOrderSignatures(): void {
		$get_menu_order = new ReflectionMethod( wpPostAble::class, 'getMenuOrder' );
		$set_menu_order = new ReflectionMethod( wpPostAble::class, 'setMenuOrder' );
		$parameter = $set_menu_order->getParameters()[0];

		self::assertSame( 'int', $get_menu_order->getReturnType()->getName() );
		self::assertFalse( $get_menu_order->getReturnType()->allowsNull() );
		self::assertSame( 'menuOrder', $parameter->getName() );
		self::assertSame( 'int', $parameter->getType()->getName() );
		self::assertFalse( $parameter->getType()->allowsNull() );
		self::assertSame( 'self', $set_menu_order->getReturnType()->getName() );
		self::assertFalse( $set_menu_order->getReturnType()->allowsNull() );
	}

	private function loadPostable( int $menu_order ): TestPostable {
		$post = new WP_Post(
			[
				'ID'                    => 23,
				'post_type'             => 'book',
				'post_title'            => 'Original title',
				'post_name'             => 'original-slug',
				'menu_order'           => $menu_order,
				'post_status'           => 'draft',
				'post_content'          => 'Original content',
				'post_content_filtered' => '{}',
			]
		);
		$this->stubLoadedPost( $post );

		return new TestPostable( 'book', 23 );
	}
}
