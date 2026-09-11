<?php

namespace iTRON\wpPostAble\Tests\Unit;

use iTRON\wpPostAble\Tests\Support\ManualPostable;
use iTRON\wpPostAble\wpPostAble;
use iTRON\wpPostAble\wpPostAbleTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use WP_Post;

final class PublicApiContractTest extends TestCase {
	public function testInterfaceAndTraitExposeExactlyTheFrozenMethods(): void {
		$expected_methods = array_keys( $this->expectedSignatures() );
		$interface_methods = $this->methodNames( new ReflectionClass( wpPostAble::class ), ReflectionMethod::IS_PUBLIC );
		$trait_methods = $this->methodNames( new ReflectionClass( wpPostAbleTrait::class ), ReflectionMethod::IS_PUBLIC );

		sort( $expected_methods );
		self::assertSame( $expected_methods, $interface_methods );
		self::assertSame( $expected_methods, $trait_methods );
	}

	public function testInterfaceAndTraitSignaturesMatchTheFrozenContract(): void {
		foreach ( $this->expectedSignatures() as $method_name => $signature ) {
			$this->assertMethodSignature( new ReflectionMethod( wpPostAble::class, $method_name ), $signature );
			$this->assertMethodSignature( new ReflectionMethod( wpPostAbleTrait::class, $method_name ), $signature );
		}
	}

	public function testCompositionSeamsAndAssociatedPostArePrivate(): void {
		$trait = new ReflectionClass( wpPostAbleTrait::class );

		self::assertTrue( $trait->getProperty( 'post' )->isPrivate() );
		self::assertTrue( $trait->getMethod( 'wpPostAble' )->isPrivate() );
		self::assertTrue( $trait->getMethod( 'loadPost' )->isPrivate() );
		self::assertTrue( $trait->getMethod( 'loadPostObject' )->isPrivate() );
		self::assertFalse( ( new ReflectionClass( wpPostAble::class ) )->hasMethod( 'wpPostAble' ) );

		$this->assertMethodSignature(
			$trait->getMethod( 'wpPostAble' ),
			[
				'return' => 'self',
				'parameters' => [
					[ 'post_type', 'string', false, null ],
					[ 'post_id', null, true, 0 ],
				],
			],
			false
		);
		$this->assertMethodSignature(
			$trait->getMethod( 'loadPost' ),
			[
				'return' => 'self',
				'parameters' => [ [ 'post_id', 'int', false, null ] ],
			],
			false
		);
		$this->assertMethodSignature(
			$trait->getMethod( 'loadPostObject' ),
			[
				'return' => 'self',
				'parameters' => [ [ 'post', 'WP_Post', false, null ] ],
			],
			false
		);
	}

	public function testManualImplementationCompilesAndUsesTheFrozenContract(): void {
		$post = new WP_Post(
			[
				'ID' => 91,
				'post_type' => 'book',
				'post_title' => 'Original',
				'post_name' => 'original',
				'menu_order' => 1,
				'post_status' => 'draft',
			]
		);
		$postable = new ManualPostable( $post );

		self::assertInstanceOf( wpPostAble::class, $postable );
		self::assertSame( $postable, $postable->setTitle( 'Changed' ) );
		self::assertNull( $postable->setParam( 'edition', 2 ) );
		self::assertSame( 2, $postable->getParam( 'edition' ) );
		self::assertSame( $postable, $postable->publish() );
		self::assertSame( 'publish', $postable->getStatus() );
		self::assertNull( $postable->deletePost() );
	}

	private function expectedSignatures(): array {
		return [
			'getPost' => [ 'return' => 'WP_Post', 'parameters' => [] ],
			'savePost' => [ 'return' => 'self', 'parameters' => [] ],
			'deletePost' => [ 'return' => 'void', 'parameters' => [] ],
			'getPostType' => [ 'return' => 'string', 'parameters' => [] ],
			'getTitle' => [ 'return' => 'string', 'parameters' => [] ],
			'setTitle' => [ 'return' => 'self', 'parameters' => [ [ 'title', 'string', false, null ] ] ],
			'getSlug' => [ 'return' => 'string', 'parameters' => [] ],
			'setSlug' => [ 'return' => 'self', 'parameters' => [ [ 'slug', 'string', false, null ] ] ],
			'getMenuOrder' => [ 'return' => 'int', 'parameters' => [] ],
			'setMenuOrder' => [ 'return' => 'self', 'parameters' => [ [ 'menuOrder', 'int', false, null ] ] ],
			'getStatus' => [ 'return' => 'string', 'parameters' => [] ],
			'setStatus' => [ 'return' => 'self', 'parameters' => [ [ 'status', 'string', false, null ] ] ],
			'setMetaField' => [
				'return' => 'self',
				'parameters' => [
					[ 'meta_key', 'string', false, null ],
					[ 'meta_value', null, false, null ],
				],
			],
			'getMetaField' => [ 'return' => null, 'parameters' => [ [ 'meta_key', 'string', false, null ] ] ],
			'getMetaFields' => [ 'return' => 'array', 'parameters' => [] ],
			'getParam' => [ 'return' => null, 'parameters' => [ [ 'param', 'string', false, null ] ] ],
			'setParam' => [
				'return' => 'void',
				'parameters' => [
					[ 'param', 'string', false, null ],
					[ 'value', null, false, null ],
				],
			],
			'publish' => [ 'return' => 'self', 'parameters' => [] ],
			'draft' => [ 'return' => 'self', 'parameters' => [] ],
		];
	}

	private function methodNames( ReflectionClass $class, int $filter ): array {
		$methods = array_map(
			static function ( ReflectionMethod $method ): string {
				return $method->getName();
			},
			$class->getMethods( $filter )
		);
		sort( $methods );

		return $methods;
	}

	private function assertMethodSignature(
		ReflectionMethod $method,
		array $expected,
		bool $public = true
	): void {
		self::assertSame( $public, $method->isPublic(), $method->getName() . ' visibility' );
		self::assertSame( $expected['return'], $this->typeName( $method->getReturnType() ), $method->getName() . ' return type' );
		if ( null !== $method->getReturnType() ) {
			self::assertFalse( $method->getReturnType()->allowsNull(), $method->getName() . ' return nullability' );
		}
		self::assertSame( count( $expected['parameters'] ), $method->getNumberOfParameters(), $method->getName() . ' parameter count' );

		foreach ( $method->getParameters() as $index => $parameter ) {
			$this->assertParameter( $parameter, $expected['parameters'][ $index ], $method->getName() );
		}
	}

	private function assertParameter( ReflectionParameter $parameter, array $expected, string $method_name ): void {
		[ $name, $type, $has_default, $default ] = $expected;
		self::assertSame( $name, $parameter->getName(), $method_name . ' parameter name' );
		self::assertSame( $type, $this->typeName( $parameter->getType() ), $method_name . ' parameter type' );
		if ( null !== $parameter->getType() ) {
			self::assertFalse( $parameter->getType()->allowsNull(), $method_name . ' parameter nullability' );
		}
		self::assertSame( $has_default, $parameter->isDefaultValueAvailable(), $method_name . ' parameter default' );
		self::assertFalse( $parameter->isPassedByReference(), $method_name . ' reference parameter' );
		self::assertFalse( $parameter->isVariadic(), $method_name . ' variadic parameter' );
		if ( $has_default ) {
			self::assertSame( $default, $parameter->getDefaultValue(), $method_name . ' default value' );
		}
	}

	private function typeName( $type ) {
		return $type instanceof ReflectionNamedType ? $type->getName() : null;
	}
}
