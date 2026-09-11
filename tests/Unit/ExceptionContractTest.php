<?php

namespace iTRON\wpPostAble\Tests\Unit;

use iTRON\wpPostAble\Exceptions\wpException;
use iTRON\wpPostAble\Exceptions\wppaCreatePostException;
use iTRON\wpPostAble\Exceptions\wppaDeletePostException;
use iTRON\wpPostAble\Exceptions\wppaException;
use iTRON\wpPostAble\Exceptions\wppaLoadPostException;
use iTRON\wpPostAble\Exceptions\wppaParamException;
use iTRON\wpPostAble\Exceptions\wppaSavePostException;
use iTRON\wpPostAble\Tests\Support\ManualPostable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use WP_Error;
use WP_Post;

final class ExceptionContractTest extends TestCase {
	public function testExceptionHierarchyAndLegacyPublicContextPropertiesAreFrozen(): void {
		$expected_properties = [
			wppaException::class => [ 'postable' ],
			wppaCreatePostException::class => [ 'error', 'postable' ],
			wppaLoadPostException::class => [ 'post_id', 'postable' ],
			wppaSavePostException::class => [ 'error', 'postable' ],
			wppaDeletePostException::class => [ 'error', 'post', 'postable' ],
			wppaParamException::class => [ 'postable' ],
		];

		foreach ( $expected_properties as $class_name => $property_names ) {
			$class = new ReflectionClass( $class_name );
			$actual_names = array_map(
				static function ( ReflectionProperty $property ): string {
					return $property->getName();
				},
				$class->getProperties( ReflectionProperty::IS_PUBLIC )
			);
			sort( $actual_names );
			sort( $property_names );

			self::assertSame( $property_names, $actual_names, $class_name . ' public properties' );
			self::assertFalse( $class->isFinal(), $class_name . ' remains extendable in 1.x' );
			foreach ( $property_names as $property_name ) {
				$property = $class->getProperty( $property_name );
				self::assertTrue( $property->isPublic() );
				self::assertFalse( $property->hasType(), $class_name . '::$' . $property_name . ' remains untyped' );
			}
		}

		self::assertSame( \Exception::class, get_parent_class( wppaException::class ) );
		foreach ( [
			wppaCreatePostException::class,
			wppaLoadPostException::class,
			wppaSavePostException::class,
			wppaDeletePostException::class,
			wppaParamException::class,
		] as $class_name ) {
			self::assertSame( wppaException::class, get_parent_class( $class_name ) );
		}
		foreach ( [ wppaCreatePostException::class, wppaSavePostException::class, wppaDeletePostException::class ] as $class_name ) {
			self::assertTrue( is_subclass_of( $class_name, wpException::class ) );
		}
		self::assertFalse( is_subclass_of( wppaLoadPostException::class, wpException::class ) );
		self::assertFalse( is_subclass_of( wppaParamException::class, wpException::class ) );
	}

	public function testConstructorSignaturesAreFrozen(): void {
		$postable = [ 'postable', 'iTRON\\wpPostAble\\wpPostAble', false, null ];
		$message = [ 'message', null, true, '' ];
		$code = [ 'code', null, true, 0 ];
		$previous = [ 'previous', 'Throwable', true, null ];

		$expected = [
			wppaException::class => [ $postable, $message, $code, $previous ],
			wppaCreatePostException::class => [
				$postable,
				[ 'error', 'WP_Error', false, null ],
				$message,
				$code,
				$previous,
			],
			wppaLoadPostException::class => [
				[ 'post_id', null, false, null ],
				$postable,
				$message,
				$code,
				$previous,
			],
			wppaSavePostException::class => [
				$postable,
				[ 'error', 'WP_Error', false, null ],
				$message,
				$code,
				$previous,
			],
			wppaDeletePostException::class => [
				$postable,
				[ 'post', 'WP_Post', false, null ],
				[ 'error', 'WP_Error', false, null ],
				$message,
				$code,
				$previous,
			],
			wppaParamException::class => [
				$postable,
				[ 'param_name', 'string', false, null ],
				[ 'operation', 'string', false, null ],
				[ 'reason', 'string', false, null ],
				[ 'json_error_code', 'int', false, null ],
				$message,
				$previous,
			],
		];

		foreach ( $expected as $class_name => $parameters ) {
			$constructor = ( new ReflectionClass( $class_name ) )->getConstructor();
			self::assertNotNull( $constructor );
			self::assertTrue( $constructor->isPublic(), $class_name . ' constructor visibility' );
			self::assertFalse( $constructor->hasReturnType() );
			self::assertCount( count( $parameters ), $constructor->getParameters() );

			foreach ( $constructor->getParameters() as $index => $parameter ) {
				[ $name, $type, $has_default, $default ] = $parameters[ $index ];
				self::assertSame( $name, $parameter->getName(), $class_name . ' constructor parameter name' );
				self::assertSame( $type, $this->typeName( $parameter->getType() ), $class_name . ' constructor parameter type' );
				self::assertSame( $has_default, $parameter->isDefaultValueAvailable(), $class_name . ' constructor default' );
				if ( null !== $parameter->getType() ) {
					self::assertSame( 'previous' === $name, $parameter->getType()->allowsNull(), $class_name . ' constructor nullability' );
				}
				if ( $has_default ) {
					self::assertSame( $default, $parameter->getDefaultValue(), $class_name . ' constructor default value' );
				}
			}
		}
	}

	public function testGetterSignaturesAndParameterConstantsAreFrozen(): void {
		$expected_getters = [
			[ wpException::class, 'getError', 'WP_Error' ],
			[ wppaException::class, 'getPostable', 'iTRON\\wpPostAble\\wpPostAble' ],
			[ wppaCreatePostException::class, 'getError', 'WP_Error' ],
			[ wppaLoadPostException::class, 'getPostID', null ],
			[ wppaSavePostException::class, 'getPost', null ],
			[ wppaSavePostException::class, 'getError', 'WP_Error' ],
			[ wppaDeletePostException::class, 'getPost', 'WP_Post' ],
			[ wppaDeletePostException::class, 'getError', 'WP_Error' ],
			[ wppaParamException::class, 'getParamName', 'string' ],
			[ wppaParamException::class, 'getOperation', 'string' ],
			[ wppaParamException::class, 'getReason', 'string' ],
			[ wppaParamException::class, 'getJsonErrorCode', 'int' ],
		];

		foreach ( $expected_getters as [ $class_name, $method_name, $return_type ] ) {
			$method = new ReflectionMethod( $class_name, $method_name );
			self::assertTrue( $method->isPublic() );
			self::assertSame( $return_type, $this->typeName( $method->getReturnType() ) );
			self::assertSame( 0, $method->getNumberOfParameters() );
		}

		self::assertSame( 'read', wppaParamException::OPERATION_READ );
		self::assertSame( 'write', wppaParamException::OPERATION_WRITE );
		self::assertSame( 'invalid_json', wppaParamException::REASON_INVALID_JSON );
		self::assertSame( 'invalid_root', wppaParamException::REASON_INVALID_ROOT );
		self::assertSame( 'encode_failed', wppaParamException::REASON_ENCODE_FAILED );
	}

	public function testConstructedExceptionsPreserveContextAndPreviousException(): void {
		$post = new WP_Post( [ 'ID' => 93, 'post_type' => 'book', 'post_title' => 'Current' ] );
		$postable = new ManualPostable( $post );
		$error = new WP_Error( 'core_failure', 'Core failed.', [ 'safe' => false ] );
		$previous = new RuntimeException( 'Previous failure.' );

		$base = new wppaException( $postable, 'Base failure.', 31, $previous );
		self::assertSame( $postable, $base->postable );
		self::assertSame( $postable, $base->getPostable() );
		$this->assertThrowableEnvelope( $base, 'Base failure.', 31, $previous );

		$create = new wppaCreatePostException( $postable, $error, 'Create failure.', 32, $previous );
		self::assertSame( $error, $create->error );
		self::assertSame( $error, $create->getError() );
		$this->assertThrowableEnvelope( $create, 'Create failure.', 32, $previous );

		$load = new wppaLoadPostException( 404, $postable, 'Load failure.', 33, $previous );
		self::assertSame( 404, $load->post_id );
		self::assertSame( 404, $load->getPostID() );
		$this->assertThrowableEnvelope( $load, 'Load failure.', 33, $previous );

		$save = new wppaSavePostException( $postable, $error, 'Save failure.', 34, $previous );
		$postable->setTitle( 'Changed after construction' );
		self::assertSame( $error, $save->error );
		self::assertSame( $error, $save->getError() );
		self::assertSame( $post, $save->getPost() );
		self::assertSame( 'Changed after construction', $save->getPost()->post_title );
		$this->assertThrowableEnvelope( $save, 'Save failure.', 34, $previous );

		$snapshot = new WP_Post( [ 'ID' => 93, 'post_type' => 'book', 'post_title' => 'Delete snapshot' ] );
		$delete = new wppaDeletePostException( $postable, $snapshot, $error, 'Delete failure.', 35, $previous );
		self::assertSame( $snapshot, $delete->post );
		self::assertSame( $snapshot, $delete->getPost() );
		self::assertSame( $error, $delete->error );
		self::assertSame( $error, $delete->getError() );
		$this->assertThrowableEnvelope( $delete, 'Delete failure.', 35, $previous );

		$param = new wppaParamException(
			$postable,
			'target',
			wppaParamException::OPERATION_WRITE,
			wppaParamException::REASON_INVALID_JSON,
			JSON_ERROR_SYNTAX,
			'Parameter failure.',
			$previous
		);
		self::assertSame( 'target', $param->getParamName() );
		self::assertSame( 'write', $param->getOperation() );
		self::assertSame( 'invalid_json', $param->getReason() );
		self::assertSame( JSON_ERROR_SYNTAX, $param->getJsonErrorCode() );
		$this->assertThrowableEnvelope( $param, 'Parameter failure.', JSON_ERROR_SYNTAX, $previous );
	}

	private function assertThrowableEnvelope(
		Throwable $exception,
		string $message,
		int $code,
		Throwable $previous
	): void {
		self::assertSame( $message, $exception->getMessage() );
		self::assertSame( $code, $exception->getCode() );
		self::assertSame( $previous, $exception->getPrevious() );
	}

	private function typeName( $type ) {
		return $type instanceof ReflectionNamedType ? $type->getName() : null;
	}
}
