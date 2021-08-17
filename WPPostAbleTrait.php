<?php
/**
 * Use this trait in conjunction WPPostAble interface only.
 *
 * By using this trait, you should assure the existence following entities in your class:
 *
 * Fields
 *  $post_type          string      Private     WP post type, associated with your class
 *  $initial_method     string      Private     Name of private method in your class (see more below)
 *
 * Methods:
 *  (any name)          callable    Method does initial setups for your needings.
 *                                  Called from @see self::loadPost() by closure.
 *                                  Method name should be initially stored to $initial_method field.
 *                                  One argument for callable
 *                                      var             $this
 *                                      Type            self
 *                                      Description     Self-object, passed by link
 *
 * Also you should call construct() method in the beginning __construct() of your class.
 */

/**
	Example for necessary fields and methods

  	private $post_type = 'super_cpt';
	private $initial_method = '_init';
 	private function _init(): callable {
		return function ( self $group ){
			// $group is linking to $this
			// Do initial actions
		};
	}
 */
namespace iTRON;

use iTRON\Exception\WPPATCreatePostException;
use iTRON\Exception\WPPATLoadPostException;
use iTRON\Exception\WPPATSavePostException;
use iTRON\WPPostAble;
use WP_Error;
use WP_Post;

trait WPPostAbleTrait{
	/**
	 * Add $post_type in your class.
	 * Example
	 * private $post_type = 'super_cpt';
	 */

	/**
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * @var string
	 * @see wp_insert_post()
	 */
	public $status = 'draft';

	/**
	 * Call this method in the beginning __construct() of your class.
	 *
	 * @param int $post_id
	 *
	 * @return $this
	 * @throws WPPATLoadPostException
	 * @throws WPPATCreatePostException
	 */
	private function construct( int $post_id = 0 ): self {
		if ( empty( $post_id ) ){
			$post_id = wp_insert_post([
				'post_type'     => $this->getPostType(),
				'post_status'   => $this->getStatus(),
				'post_title'    => '',
				'post_content'  => 'Empty.',
			], true );
			if ( empty( $post_id ) || is_wp_error( $post_id ) ){
				$error = empty( $post_id ) ? new WP_Error() : $post_id;
				/** @var WPPostAble $this */
				throw new WPPATCreatePostException( $this, $error, $error->get_error_messages() );
			}
		}

		return $this->loadPost( $post_id );
	}

	public function getPost(): WP_Post{
		return $this->post;
	}

	public function getPostType(): string{
		return $this->post_type;
	}

	/**
	 * @throws WPPATSavePostException
	 */
	public function savePost(): self {
		$result = wp_update_post( $this->post, true );
		if ( empty( $result ) || is_wp_error( $result ) ){
			$error = empty( $result ) ? new WP_Error() : $result;
			/** @var WPPostAble $this */
			throw new WPPATSavePostException( $this, $error, $error->get_error_messages() );
		}
		return $this;
	}

	/**
	 * Loads and initiates all Group data from WP post.
	 * @return $this
	 * @throws WPPATLoadPostException
	 */
	public function loadPost( int $post_id ): self {

		if (
			empty( $post_id ) ||
			empty( $post = get_post( $post_id ) ) ||
			! apply_filters( __CLASS__ . '\WPPostAbleTrait\loadPost\equal_post_type',
				apply_filters( '\WPPostAbleTrait\loadPost\equal_post_type', $post->post_type === $this->post_type, __CLASS__ ), __CLASS__
			)
		){
			/** @var WPPostAble $this */
			throw new WPPATLoadPostException( $post_id, $this, "Incorrect post id [ $post_id ]");
		}

		$this->post = $post;

		/**
		 * Call callable method from main class
		 */
		if (
			is_callable( $callback = call_user_func( [$this, $this->initial_method] ) ) &&
			is_callable( $callback )
		){
			call_user_func_array( $callback, [ & $this ] );
		}

		do_action_ref_array( '\WPPostAbleTrait\loadPost\loading', [ & $this, __CLASS__ ] );
		do_action_ref_array( __CLASS__ . '\WPPostAbleTrait\loadPost\loading', [ & $this, __CLASS__ ] );
		return $this;
	}

	public function getTitle(): string{
		return $this->post->post_title;
	}

	public function setTitle( string $title ): self {
		$this->post->post_title = $title;
		return $this;
	}

	public function getStatus(): string{
		return $this->status;
	}

	public function setStatus( string $status ): self {
		$this->status = $status;
		$this->post->post_status = $status;
		return $this;
	}

	/**
	 * @throws WPPATSavePostException
	 */
	public function publish(): self {
		$this->setStatus( 'publish' );
		return $this->savePost();
	}

	/**
	 * @throws WPPATSavePostException
	 */
	public function draft(): self {
		$this->setStatus( 'draft' );
		return $this->savePost();
	}
}
