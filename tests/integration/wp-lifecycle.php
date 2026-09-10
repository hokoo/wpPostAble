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
	$item->savePost();

	$loaded = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'wpPostAble integration lifecycle' === $loaded->getTitle(), 'title did not survive save/reload' );
	wppa_integration_assert( 'draft' === $loaded->getStatus(), 'draft status did not survive save/reload' );
	wppa_integration_assert( '' === $loaded->getParam( 'empty' ), 'empty parameter did not survive save/reload' );
	wppa_integration_assert( $unicode_param === $loaded->getParam( 'unicode' ), 'Unicode parameter did not survive save/reload' );
	wppa_integration_assert(
		wp_json_encode( $nested_param, JSON_UNESCAPED_UNICODE ) === wp_json_encode( $loaded->getParam( 'nested' ), JSON_UNESCAPED_UNICODE ),
		'nested parameter did not survive save/reload'
	);
	wppa_integration_assert( 'plain scalar' === $loaded->getMetaField( 'wppa_scalar' ), 'scalar meta did not survive save/reload' );
	wppa_integration_assert( $structured_meta === $loaded->getMetaField( 'wppa_structured' ), 'structured meta did not survive save/reload' );

	global $wpdb;
	$raw_scalar = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
			$post_id,
			'wppa_scalar'
		)
	);
	$raw_structured = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
			$post_id,
			'wppa_structured'
		)
	);
	wppa_integration_assert( 'plain scalar' === $raw_scalar, 'WordPress changed scalar meta storage' );
	wppa_integration_assert( is_serialized( $raw_structured ), 'structured meta was not serialized by WordPress' );

	$loaded->publish();
	$published = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'publish' === $published->getStatus(), 'publish() did not persist publish status' );

	$published->draft();
	$draft = new WpPostAbleLocalItem( $post_id );
	wppa_integration_assert( 'draft' === $draft->getStatus(), 'draft() did not persist draft status' );

	// Cleanup deliberately uses Core. Library deletePost() has a separate known defect.
	wppa_integration_assert( false !== wp_delete_post( $post_id, true ), 'Core force-delete failed' );
	$post_id = 0;
	wppa_integration_assert( 0 === wppa_integration_fixture_count(), 'Core cleanup left fixture posts behind' );

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
			'empty_unicode_nested_params',
			'scalar_and_serialized_structured_meta',
			'publish_and_draft',
			'core_force_delete_cleanup',
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
