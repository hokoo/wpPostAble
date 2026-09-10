<?php

namespace iTRON\wpPostAble\Tests\Support;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WP_Error;

abstract class WordPressTestCase extends TestCase {
	protected $filterCalls = [];
	protected $actionCalls = [];
	protected $filterOverrides = [];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'is_wp_error' )->alias(
			static function ( $value ): bool {
				return $value instanceof WP_Error;
			}
		);
		Functions\when( 'maybe_unserialize' )->alias(
			static function ( $value ) {
				if ( ! is_string( $value ) || ! preg_match( '/^[aObisdN]:/', $value ) ) {
					return $value;
				}

				$result = @unserialize( $value );
				return false === $result && 'b:0;' !== $value ? $value : $result;
			}
		);
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value, ...$arguments ) {
				$this->filterCalls[] = [ $hook, array_merge( [ $value ], $arguments ) ];

				if ( isset( $this->filterOverrides[ $hook ] ) ) {
					return ( $this->filterOverrides[ $hook ] )( $value, ...$arguments );
				}

				return $value;
			}
		);
		Functions\when( 'do_action' )->alias(
			function ( $hook, ...$arguments ): void {
				$this->actionCalls[] = [ $hook, $arguments ];
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	protected function stubLoadedPost( \WP_Post $post, array $meta = [ 'fixture' => [ 'value' ] ] ): void {
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_post_meta' )->justReturn( $meta );
	}
}
