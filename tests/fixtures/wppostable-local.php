<?php
/**
 * Plugin Name: wpPostAble Local Fixture
 * Description: Provides a concrete wpPostAble consumer for local development.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wppa_local_autoload = '/workspace/vendor/autoload.php';
if ( ! is_file( $wppa_local_autoload ) ) {
	trigger_error( 'wpPostAble local fixture requires /workspace/vendor/autoload.php.', E_USER_WARNING );
	return;
}

require_once $wppa_local_autoload;

final class WpPostAbleLocalItem implements \iTRON\wpPostAble\wpPostAble {
	use \iTRON\wpPostAble\wpPostAbleTrait;

	const POST_TYPE = 'wppa_item';

	/**
	 * @param int|WP_Post|null $post_id Existing post or ID, or null/zero to create one.
	 */
	public function __construct( $post_id = 0 ) {
		$this->wpPostAble( self::POST_TYPE, $post_id );
	}
}

add_action(
	'init',
	static function () {
		register_post_type(
			WpPostAbleLocalItem::POST_TYPE,
			array(
				'label'        => 'wpPostAble Items',
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'page-attributes' ),
			)
		);
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	final class WpPostAbleLocalCommand {
		/**
		 * Exercises the library against the current WordPress database.
		 */
		public function smoke() {
			$post_id = 0;

			try {
				$item    = new WpPostAbleLocalItem();
				$post_id = $item->getPost()->ID;
				$item->setTitle( 'wpPostAble local smoke' );
				$item->setParam( 'source', 'localdev' );
				$item->setMetaField( 'wppa_structured', array( 'round_trip' => true ) );
				$item->publish();

				$loaded = new WpPostAbleLocalItem( $post_id );
				$this->assertSame( 'wpPostAble local smoke', $loaded->getTitle(), 'title round trip' );
				$this->assertSame( 'publish', $loaded->getStatus(), 'status round trip' );
				$this->assertSame( 'localdev', $loaded->getParam( 'source' ), 'parameter round trip' );
				$this->assertSame(
					array( 'round_trip' => true ),
					$loaded->getMetaField( 'wppa_structured' ),
					'meta round trip'
				);
			} catch ( Throwable $exception ) {
				if ( $post_id ) {
					wp_delete_post( $post_id, true );
				}
				WP_CLI::error( $exception->getMessage() );
			}

			// Cleanup intentionally uses WordPress Core. The library delete behavior
			// has a known defect and belongs to its dedicated regression test.
			if ( ! wp_delete_post( $post_id, true ) ) {
				WP_CLI::error( 'Smoke assertions passed, but fixture cleanup failed.' );
			}

			WP_CLI::success( 'wpPostAble create/save/reload/meta/param/publish smoke passed.' );
		}

		private function assertSame( $expected, $actual, $label ) {
			if ( $expected !== $actual ) {
				throw new RuntimeException(
					sprintf( '%s failed: expected %s, got %s', $label, var_export( $expected, true ), var_export( $actual, true ) )
				);
			}
		}
	}

	WP_CLI::add_command( 'wppostable', 'WpPostAbleLocalCommand' );
}
