<?php
/**
 * Real-WordPress lifecycle assertions, executed with `wp eval-file`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress is not loaded.\n" );
	exit( 1 );
}

/**
 * @param bool   $condition Assertion result.
 * @param string $message   Failure description.
 */
function wppa_integration_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'Integration assertion failed: ' . $message );
	}
}

/**
 * Count fixture posts directly so no post-status query defaults hide leftovers.
 *
 * @return int
 */
function wppa_integration_fixture_count() {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
			WpPostAbleLocalItem::POST_TYPE
		)
	);
}

/**
 * Read raw meta rows in insertion order without WordPress deserialization.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 * @return string[]
 */
function wppa_integration_raw_meta_values( $post_id, $meta_key ) {
	global $wpdb;

	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC",
			$post_id,
			$meta_key
		)
	);
}

/**
 * Assert that untouched multi-value and structured metadata remain byte-for-byte stable.
 *
 * @param int      $post_id                 Post ID.
 * @param string[] $expected_multi_values   Expected raw values.
 * @param string   $expected_structured_raw Expected serialized value.
 * @param string   $phase                   Save phase label.
 */
function wppa_integration_assert_untouched_meta( $post_id, $expected_multi_values, $expected_structured_raw, $phase ) {
	wppa_integration_assert(
		$expected_multi_values === wppa_integration_raw_meta_values( $post_id, 'wppa_multi' ),
		$phase . ' changed multi-value meta rows'
	);
	wppa_integration_assert(
		array( $expected_structured_raw ) === wppa_integration_raw_meta_values( $post_id, 'wppa_structured' ),
		$phase . ' changed raw structured meta'
	);
}

$post_id = 0;
$result  = array();

try {
	wppa_integration_assert( class_exists( 'WpPostAbleLocalItem' ), 'fixture model was not loaded as an MU-plugin' );
	wppa_integration_assert( post_type_exists( WpPostAbleLocalItem::POST_TYPE ), 'fixture post type was not registered' );
	wppa_integration_assert( 0 === wppa_integration_fixture_count(), 'profile did not start with a clean fixture table' );

	$item    = new WpPostAbleLocalItem();
	$post_id = $item->getPost()->ID;

	wppa_integration_assert( $post_id > 0, 'model creation did not return a persisted post ID' );
	wppa_integration_assert( WpPostAbleLocalItem::POST_TYPE === $item->getPostType(), 'model post type changed' );
	wppa_integration_assert( 'draft' === $item->getStatus(), 'new model did not start as a draft' );

	$unicode_param = 'Привет, საქართველო 👋';
	$nested_param  = array(
		'level_one' => array(
			'enabled' => true,
			'count'   => 2,
		),
		'items'     => array( 'one', 'два', 'სამი' ),
	);
	$structured_meta = array(
		'round_trip' => true,
		'nested'     => array(
			'answer'  => 42,
			'unicode' => 'метаданные',
		),
	);

	$item
		->setTitle( 'wpPostAble integration lifecycle' )
		->setMetaField( 'wppa_scalar', 'plain scalar' )
		->setMetaField( 'wppa_structured', $structured_meta );
	$item->setParam( 'empty', '' );
	$item->setParam( 'unicode', $unicode_param );
	$item->setParam( 'nested', $nested_param );
	$item->setParam( '0', 'numeric-key value' );
	$item->savePost();

	wppa_integration_assert( false !== add_post_meta( $post_id, 'wppa_multi', 'first value' ), 'first multi-value meta row was not added' );
	wppa_integration_assert( false !== add_post_meta( $post_id, 'wppa_multi', 'second value' ), 'second multi-value meta row was not added' );

	$loaded = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'wpPostAble integration lifecycle' === $loaded->getTitle(), 'title did not survive save/reload' );
	wppa_integration_assert( 'draft' === $loaded->getStatus(), 'draft status did not survive save/reload' );
	wppa_integration_assert( '' === $loaded->getParam( 'empty' ), 'empty parameter did not survive save/reload' );
	wppa_integration_assert( $unicode_param === $loaded->getParam( 'unicode' ), 'Unicode parameter did not survive save/reload' );
	wppa_integration_assert(
		wp_json_encode( $nested_param, JSON_UNESCAPED_UNICODE ) === wp_json_encode( $loaded->getParam( 'nested' ), JSON_UNESCAPED_UNICODE ),
		'nested parameter did not survive save/reload'
	);
	wppa_integration_assert( 'numeric-key value' === $loaded->getParam( '0' ), 'numeric parameter key did not survive save/reload' );
	wppa_integration_assert( 'plain scalar' === $loaded->getMetaField( 'wppa_scalar' ), 'scalar meta did not survive save/reload' );
	wppa_integration_assert( $structured_meta === $loaded->getMetaField( 'wppa_structured' ), 'structured meta did not survive save/reload' );
	wppa_integration_assert( 'first value' === $loaded->getMetaField( 'wppa_multi' ), 'model did not expose the first multi-value meta row' );

	$raw_scalar     = wppa_integration_raw_meta_values( $post_id, 'wppa_scalar' )[0];
	$raw_structured = wppa_integration_raw_meta_values( $post_id, 'wppa_structured' )[0];
	$multi_values   = array( 'first value', 'second value' );
	wppa_integration_assert( 'plain scalar' === $raw_scalar, 'WordPress changed scalar meta storage' );
	wppa_integration_assert( is_serialized( $raw_structured ), 'structured meta was not serialized by WordPress' );
	wppa_integration_assert_untouched_meta( $post_id, $multi_values, $raw_structured, 'initial reload' );

	$loaded->setTitle( 'wpPostAble title-only save' )->savePost();
	wppa_integration_assert_untouched_meta( $post_id, $multi_values, $raw_structured, 'title-only save' );

	$loaded->setMetaField( 'wppa_unrelated', 'unrelated value' )->savePost();
	wppa_integration_assert( 'unrelated value' === get_post_meta( $post_id, 'wppa_unrelated', true ), 'explicit single-meta save failed' );
	wppa_integration_assert_untouched_meta( $post_id, $multi_values, $raw_structured, 'unrelated single-meta save' );

	$valid_content = $loaded->getPost()->post_content_filtered;
	$malformed_content = '{"broken":';
	$loaded->getPost()->post_content_filtered = $malformed_content;
	$read_exception = null;
	try {
		$loaded->getParam( 'broken' );
	} catch ( \iTRON\wpPostAble\Exceptions\wppaParamException $exception ) {
		$read_exception = $exception;
	}
	wppa_integration_assert( $read_exception instanceof \iTRON\wpPostAble\Exceptions\wppaParamException, 'malformed JSON read did not throw wppaParamException' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::OPERATION_READ === $read_exception->getOperation(), 'malformed JSON read reported wrong operation' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::REASON_INVALID_JSON === $read_exception->getReason(), 'malformed JSON read reported wrong reason' );
	wppa_integration_assert( $malformed_content === $loaded->getPost()->post_content_filtered, 'malformed JSON read changed post content' );

	$write_exception = null;
	try {
		$loaded->setParam( 'broken', 'value' );
	} catch ( \iTRON\wpPostAble\Exceptions\wppaParamException $exception ) {
		$write_exception = $exception;
	}
	wppa_integration_assert( $write_exception instanceof \iTRON\wpPostAble\Exceptions\wppaParamException, 'malformed JSON write did not throw wppaParamException' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::OPERATION_WRITE === $write_exception->getOperation(), 'malformed JSON write reported wrong operation' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::REASON_INVALID_JSON === $write_exception->getReason(), 'malformed JSON write reported wrong reason' );
	wppa_integration_assert( $malformed_content === $loaded->getPost()->post_content_filtered, 'malformed JSON write changed post content' );
	$loaded->getPost()->post_content_filtered = $valid_content;

	$utf8_exception = null;
	try {
		$loaded->setParam( 'invalid_utf8', "\xB1\x31" );
	} catch ( \iTRON\wpPostAble\Exceptions\wppaParamException $exception ) {
		$utf8_exception = $exception;
	}
	wppa_integration_assert( $utf8_exception instanceof \iTRON\wpPostAble\Exceptions\wppaParamException, 'invalid UTF-8 write did not throw wppaParamException' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::OPERATION_WRITE === $utf8_exception->getOperation(), 'invalid UTF-8 write reported wrong operation' );
	wppa_integration_assert( \iTRON\wpPostAble\Exceptions\wppaParamException::REASON_ENCODE_FAILED === $utf8_exception->getReason(), 'invalid UTF-8 write reported wrong reason' );
	wppa_integration_assert( JSON_ERROR_UTF8 === $utf8_exception->getJsonErrorCode(), 'invalid UTF-8 write reported wrong JSON error code' );
	wppa_integration_assert( $valid_content === $loaded->getPost()->post_content_filtered, 'invalid UTF-8 write changed valid post content' );

	$loaded->publish();
	$published = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'publish' === $published->getStatus(), 'publish() did not persist publish status' );
	wppa_integration_assert_untouched_meta( $post_id, $multi_values, $raw_structured, 'publish save' );

	$published->draft();
	$draft = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'draft' === $draft->getStatus(), 'draft() did not persist draft status' );
	wppa_integration_assert_untouched_meta( $post_id, $multi_values, $raw_structured, 'draft save' );
	wppa_integration_assert( '' === $draft->getParam( 'empty' ), 'empty parameter changed across later saves' );
	wppa_integration_assert( $unicode_param === $draft->getParam( 'unicode' ), 'Unicode parameter changed across later saves' );
	wppa_integration_assert(
		wp_json_encode( $nested_param, JSON_UNESCAPED_UNICODE ) === wp_json_encode( $draft->getParam( 'nested' ), JSON_UNESCAPED_UNICODE ),
		'nested parameter changed across later saves'
	);
	wppa_integration_assert( 'numeric-key value' === $draft->getParam( '0' ), 'numeric parameter key changed across later saves' );

	$before_delete_contexts = array();
	$after_delete_contexts  = array();
	$before_delete_hook = static function ( $id, $post, $class ) use ( &$before_delete_contexts ) {
		$before_delete_contexts[] = array( $id, $post, $class );
	};
	$after_delete_hook = static function ( $id, $post, $class ) use ( &$after_delete_contexts ) {
		$after_delete_contexts[] = array( $id, $post, $class );
	};
	add_action( '\wpPostAbleTrait\deletePost\beforeDeletePost', $before_delete_hook, 10, 3 );
	add_action( '\wpPostAbleTrait\deletePost\afterDeletePost', $after_delete_hook, 10, 3 );

	$delete_post          = $draft->getPost();
	$blocked_force_delete = null;
	$block_delete = static function ( $delete, $post, $force_delete ) use ( $post_id, &$blocked_force_delete ) {
		$blocked_force_delete = $force_delete;
		return (int) $post->ID === (int) $post_id ? false : $delete;
	};
	add_filter( 'pre_delete_post', $block_delete, 10, 3 );
	$delete_exception = null;
	try {
		$draft->deletePost();
	} catch ( \iTRON\wpPostAble\Exceptions\wppaDeletePostException $exception ) {
		$delete_exception = $exception;
	} finally {
		remove_filter( 'pre_delete_post', $block_delete, 10 );
	}

	wppa_integration_assert( $delete_exception instanceof \iTRON\wpPostAble\Exceptions\wppaDeletePostException, 'blocked Core delete did not throw wppaDeletePostException' );
	wppa_integration_assert( $draft === $delete_exception->getPostable(), 'delete exception did not preserve postable' );
	wppa_integration_assert( $delete_post === $delete_exception->getPost(), 'delete exception did not preserve original post' );
	wppa_integration_assert( '' !== $delete_exception->getError()->get_error_code(), 'delete exception error code was empty' );
	wppa_integration_assert( '' !== $delete_exception->getError()->get_error_message(), 'delete exception error message was empty' );
	wppa_integration_assert( false === $blocked_force_delete, 'library delete changed the default Core force behavior' );
	wppa_integration_assert( $delete_post === $draft->getPost(), 'failed delete invalidated model post' );
	wppa_integration_assert( get_post( $post_id ) instanceof WP_Post, 'blocked delete removed the database row' );
	wppa_integration_assert( 1 === count( $before_delete_contexts ), 'blocked delete emitted an unexpected number of before hooks' );
	wppa_integration_assert( 0 === count( $after_delete_contexts ), 'blocked delete emitted an after hook' );
	wppa_integration_assert(
		array( $post_id, $delete_post, WpPostAbleLocalItem::class ) === $before_delete_contexts[0],
		'blocked delete before hook lost original context'
	);

	$draft->deletePost();
	remove_action( '\wpPostAbleTrait\deletePost\beforeDeletePost', $before_delete_hook, 10 );
	remove_action( '\wpPostAbleTrait\deletePost\afterDeletePost', $after_delete_hook, 10 );
	wppa_integration_assert( 2 === count( $before_delete_contexts ), 'successful retry did not emit one additional before hook' );
	wppa_integration_assert( 1 === count( $after_delete_contexts ), 'successful retry did not emit one after hook' );
	wppa_integration_assert(
		array( $post_id, $delete_post, WpPostAbleLocalItem::class ) === $before_delete_contexts[1],
		'successful delete before hook lost original context'
	);
	wppa_integration_assert(
		array( $post_id, $delete_post, WpPostAbleLocalItem::class ) === $after_delete_contexts[0],
		'successful delete after hook lost original context'
	);
	wppa_integration_assert( null === get_post( $post_id ), 'library delete left the database row behind' );
	wppa_integration_assert( 0 === wppa_integration_fixture_count(), 'library delete left fixture posts behind' );

	$post_id = 0;

	$result = array(
		'status'             => 'pass',
		'profile'            => getenv( 'WPPA_IT_PROFILE' ),
		'wordpress_version'  => get_bloginfo( 'version' ),
		'php_version'        => PHP_VERSION,
		'post_type'          => WpPostAbleLocalItem::POST_TYPE,
		'assertions'         => array(
			'fixture_mu_plugin',
			'create_save_reload',
			'title_and_status',
			'empty_unicode_nested_numeric_params',
			'param_exception_contracts',
			'scalar_and_serialized_structured_meta',
			'dirty_meta_preservation_across_saves',
			'structured_meta_raw_stability',
			'publish_and_draft',
			'delete_failure_state_and_hooks',
			'library_delete_cleanup',
		),
		'remaining_fixtures' => wppa_integration_fixture_count(),
	);
} catch ( Throwable $exception ) {
	if ( $post_id > 0 ) {
		wp_delete_post( $post_id, true );
	}

	fwrite( STDERR, $exception->getMessage() . "\n" );
	exit( 1 );
}

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
echo "\n";
