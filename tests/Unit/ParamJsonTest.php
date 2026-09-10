<?php

namespace iTRON\wpPostAble\Tests\Unit;

use iTRON\wpPostAble\Exceptions\wppaParamException;
use iTRON\wpPostAble\Tests\Support\TestPostable;
use iTRON\wpPostAble\Tests\Support\WordPressTestCase;
use WP_Post;

final class ParamJsonTest extends WordPressTestCase {
	public function testEmptyWordPressFieldActsAsAnEmptyMap(): void {
		$postable = $this->postableWithContent( '' );

		self::assertNull( $postable->getParam( 'missing' ) );
		self::assertSame( '', $postable->getPost()->post_content_filtered );
		self::assertNull( $postable->setParam( 'first', 'value' ) );
		self::assertSame( 'value', $postable->getParam( 'first' ) );
		self::assertInstanceOf( \stdClass::class, json_decode( $postable->getPost()->post_content_filtered ) );
	}

	/**
	 * @dataProvider emptyMapProvider
	 */
	public function testJsonObjectAndArrayCanRepresentAnEmptyMap( string $content ): void {
		$postable = $this->postableWithContent( $content );

		self::assertNull( $postable->getParam( 'missing' ) );
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	public function emptyMapProvider(): array {
		return [
			'object' => [ '{}' ],
			'array'  => [ '[]' ],
		];
	}

	public function testObjectMapPreservesSuccessfulValueShapes(): void {
		$content = '{"string":"Привет","null":null,"object":{"enabled":true},"list":[1,2,3]}';
		$postable = $this->postableWithContent( $content );

		self::assertSame( 'Привет', $postable->getParam( 'string' ) );
		self::assertNull( $postable->getParam( 'null' ) );
		self::assertEquals( (object) [ 'enabled' => true ], $postable->getParam( 'object' ) );
		self::assertSame( [ 1, 2, 3 ], $postable->getParam( 'list' ) );
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	public function testArrayRootIsReadAsAMapAndNormalizedOnlyAfterAWrite(): void {
		$content = '["zero",{"nested":true}]';
		$postable = $this->postableWithContent( $content );

		self::assertSame( 'zero', $postable->getParam( '0' ) );
		self::assertEquals( (object) [ 'nested' => true ], $postable->getParam( '1' ) );
		self::assertSame( $content, $postable->getPost()->post_content_filtered );

		$postable->setParam( '2', 'two' );

		self::assertSame( 'zero', $postable->getParam( '0' ) );
		self::assertEquals( (object) [ 'nested' => true ], $postable->getParam( '1' ) );
		self::assertSame( 'two', $postable->getParam( '2' ) );
		self::assertInstanceOf( \stdClass::class, json_decode( $postable->getPost()->post_content_filtered ) );
	}

	public function testNumericStringKeysRemainObjectProperties(): void {
		$postable = $this->postableWithContent( '' );

		$postable->setParam( '0', 'zero' );
		$postable->setParam( '01', 'leading zero' );

		self::assertSame( 'zero', $postable->getParam( '0' ) );
		self::assertSame( 'leading zero', $postable->getParam( '01' ) );
		self::assertInstanceOf( \stdClass::class, json_decode( $postable->getPost()->post_content_filtered ) );
	}

	/**
	 * @dataProvider malformedJsonProvider
	 */
	public function testMalformedJsonThrowsOnReadAndDoesNotChangeContent( string $content ): void {
		$postable = $this->postableWithContent( $content );

		$exception = $this->captureParamException(
			static function () use ( $postable ): void {
				$postable->getParam( 'target' );
			}
		);

		$this->assertExceptionContract(
			$exception,
			$postable,
			'target',
			wppaParamException::OPERATION_READ,
			wppaParamException::REASON_INVALID_JSON,
			JSON_ERROR_SYNTAX
		);
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	/**
	 * @dataProvider malformedJsonProvider
	 */
	public function testMalformedJsonThrowsOnWriteAndDoesNotChangeContent( string $content ): void {
		$postable = $this->postableWithContent( $content );

		$exception = $this->captureParamException(
			static function () use ( $postable ): void {
				$postable->setParam( 'target', 'replacement' );
			}
		);

		$this->assertExceptionContract(
			$exception,
			$postable,
			'target',
			wppaParamException::OPERATION_WRITE,
			wppaParamException::REASON_INVALID_JSON,
			JSON_ERROR_SYNTAX
		);
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	public function malformedJsonProvider(): array {
		return [
			'truncated object' => [ '{"broken":' ],
			'whitespace only'  => [ " \t\n" ],
		];
	}

	/**
	 * @dataProvider scalarRootProvider
	 */
	public function testScalarRootThrowsOnReadAndWriteWithoutMutation( string $content ): void {
		$postable = $this->postableWithContent( $content );

		$read_exception = $this->captureParamException(
			static function () use ( $postable ): void {
				$postable->getParam( 'target' );
			}
		);
		$this->assertExceptionContract(
			$read_exception,
			$postable,
			'target',
			wppaParamException::OPERATION_READ,
			wppaParamException::REASON_INVALID_ROOT,
			JSON_ERROR_NONE
		);

		$write_exception = $this->captureParamException(
			static function () use ( $postable ): void {
				$postable->setParam( 'target', 'replacement' );
			}
		);
		$this->assertExceptionContract(
			$write_exception,
			$postable,
			'target',
			wppaParamException::OPERATION_WRITE,
			wppaParamException::REASON_INVALID_ROOT,
			JSON_ERROR_NONE
		);
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	public function scalarRootProvider(): array {
		return [
			'null'    => [ 'null' ],
			'boolean' => [ 'true' ],
			'number'  => [ '42' ],
			'string'  => [ '"text"' ],
		];
	}

	/**
	 * @dataProvider encodingFailureProvider
	 */
	public function testEncodingFailuresAreExplicitAndAtomic( callable $value_factory, int $error_code ): void {
		$content = '{"existing":"kept"}';
		$postable = $this->postableWithContent( $content );
		$value = $value_factory();

		try {
			$exception = $this->captureParamException(
				static function () use ( $postable, $value ): void {
					$postable->setParam( 'target', $value );
				}
			);

			$this->assertExceptionContract(
				$exception,
				$postable,
				'target',
				wppaParamException::OPERATION_WRITE,
				wppaParamException::REASON_ENCODE_FAILED,
				$error_code
			);
			self::assertSame( $content, $postable->getPost()->post_content_filtered );
		} finally {
			if ( is_resource( $value ) ) {
				fclose( $value );
			}
		}
	}

	public function encodingFailureProvider(): array {
		return [
			'invalid UTF-8' => [
				static function () {
					return "\xB1\x31";
				},
				JSON_ERROR_UTF8,
			],
			'unsupported resource' => [
				static function () {
					return fopen( 'php://memory', 'r' );
				},
				JSON_ERROR_UNSUPPORTED_TYPE,
			],
			'recursive array' => [
				static function () {
					$value = [];
					$value['self'] = &$value;
					return $value;
				},
				JSON_ERROR_RECURSION,
			],
		];
	}

	public function testThrowingJsonSerializableIsWrappedWithoutMutation(): void {
		$content = '{"existing":"kept"}';
		$postable = $this->postableWithContent( $content );
		$previous = new \RuntimeException( 'Encoder callback failed.' );
		$value = new class( $previous ) implements \JsonSerializable {
			private $exception;

			public function __construct( \RuntimeException $exception ) {
				$this->exception = $exception;
			}

			#[\ReturnTypeWillChange]
			public function jsonSerialize() {
				throw $this->exception;
			}
		};

		$exception = $this->captureParamException(
			static function () use ( $postable, $value ): void {
				$postable->setParam( 'target', $value );
			}
		);

		$this->assertExceptionContract(
			$exception,
			$postable,
			'target',
			wppaParamException::OPERATION_WRITE,
			wppaParamException::REASON_ENCODE_FAILED,
			JSON_ERROR_NONE
		);
		self::assertSame( $previous, $exception->getPrevious() );
		self::assertSame( $content, $postable->getPost()->post_content_filtered );
	}

	private function postableWithContent( string $content ): TestPostable {
		$post = new WP_Post(
			[
				'ID'                    => 23,
				'post_type'             => 'book',
				'post_title'            => 'Parameters',
				'post_status'           => 'draft',
				'post_content_filtered' => $content,
			]
		);
		$this->stubLoadedPost( $post );

		return new TestPostable( 'book', 23 );
	}

	private function captureParamException( callable $callback ): wppaParamException {
		try {
			$callback();
			self::fail( 'Expected parameter operation to fail.' );
		} catch ( wppaParamException $exception ) {
			return $exception;
		}
	}

	private function assertExceptionContract(
		wppaParamException $exception,
		TestPostable $postable,
		string $param,
		string $operation,
		string $reason,
		int $json_error_code
	): void {
		self::assertSame( $postable, $exception->getPostable() );
		self::assertSame( $param, $exception->getParamName() );
		self::assertSame( $operation, $exception->getOperation() );
		self::assertSame( $reason, $exception->getReason() );
		self::assertSame( $json_error_code, $exception->getJsonErrorCode() );
		self::assertSame( $json_error_code, $exception->getCode() );
		self::assertStringNotContainsString( $postable->getPost()->post_content_filtered, $exception->getMessage() );
	}
}
