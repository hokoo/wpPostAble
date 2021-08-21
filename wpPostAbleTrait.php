<?php
/**
 * Use this trait in conjunction wpPostAble interface only.
 *
 * By using this trait, you should call wpPostAble( $post_type, $post_id ) method
 * in the beginning __construct() of your class.
 * Pass to it two parameters
 *      $post_type      string      WP post type, associated with your class
 *      $post_id        int         Post ID for existing post, or nothing for creating new post
 */

namespace iTRON\wpPostAble;

use iTRON\wpPostAble\Exception\wppaCreatePostException;
use iTRON\wpPostAble\Exception\wppaLoadPostException;
use iTRON\wpPostAble\Exception\wppaSavePostException;
use WP_Error;
use WP_Post;

trait wpPostAbleTrait{
	/**
	 * @var string
	 */
	private $post_type = '';

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
	 * @var array
	 */
	private $post_meta = [];

	/**
	 * Call this method in the beginning __construct() of your class.
	 *
	 * @param string $post_type
	 * @param int $post_id
	 *
	 * @return $this
	 * @throws wppaCreatePostException
	 * @throws wppaLoadPostException
	 */
	private function wpPostAble( string $post_type, int $post_id = 0 ): self {

		if ( $this->post instanceof WP_Post ) return $this;

		$this->post_type = $post_type;

		if ( empty( $post_id ) ){
			$post_id = wp_insert_post([
				'post_type'     => $this->getPostType(),
				'post_status'   => $this->getStatus(),
				'post_title'    => '',
				'post_content'  => 'Empty.',
			], true );

			if ( empty( $post_id ) || is_wp_error( $post_id ) ){
				$error = empty( $post_id ) ? new WP_Error() : $post_id;
				/** @var wpPostAble $this */
				throw new wppaCreatePostException( $this, $error, $error->get_error_messages() );
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
	 * @throws wppaSavePostException
	 */
	public function savePost(): self {
		$postData = get_object_vars( $this->post );
		$postData[ 'meta_input' ] = $this->post_meta;
		$result = wp_update_post( $postData, true );
		if ( empty( $result ) || is_wp_error( $result ) ){
			$error = empty( $result ) ? new WP_Error() : $result;
			/** @var wpPostAble $this */
			throw new wppaSavePostException( $this, $error, $error->get_error_messages() );
		}
		return $this;
	}

	/**
	 * Loads and initiates all Group data from WP post.
	 * @return $this
	 * @throws wppaLoadPostException
	 */
	private function loadPost( int $post_id ): self {

		if (
			empty( $post_id ) ||
			empty( $post = get_post( $post_id ) )
		){
			/** @var wpPostAble $this */
			throw new wppaLoadPostException( $post_id, $this, "Incorrect post id [ $post_id ]");
		}

		if (
			! apply_filters( __CLASS__ . '\wpPostAbleTrait\loadPost\equalPostType',
				apply_filters( '\wpPostAbleTrait\loadPost\equalPostType', $post->post_type === $this->post_type, __CLASS__ ), __CLASS__
			)
		){
			/** @var wpPostAble $this */
			throw new wppaLoadPostException( $post_id, $this,
				"Incompatible post type. Class type is \"$this->post_type\", trying to load \"$post->post_type\""
			);
		}

		$this->post = $post;

		do_action_ref_array( '\wpPostAbleTrait\loadPost\loading', [ & $this, __CLASS__ ] );
		do_action_ref_array( __CLASS__ . '\wpPostAbleTrait\loadPost\loading', [ & $this, __CLASS__ ] );
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
	 * @throws wppaSavePostException
	 */
	public function publish(): self {
		$this->setStatus( 'publish' );
		return $this->savePost();
	}

	/**
	 * @throws wppaSavePostException
	 */
	public function draft(): self {
		$this->setStatus( 'draft' );
		return $this->savePost();
	}

	/**
	 * @param string $meta_key
	 * @param mixed $meta_value
	 *
	 * @return $this
	 */
	public function setMetaField( string $meta_key, $meta_value ): self {
		$this->post_meta[ $meta_key ] = $meta_value;
		return $this;
	}

	/**
	 * @param string $meta_key
	 *
	 * @return mixed|null
	 */
	public function getMetaField( string $meta_key ){
		return $this->post_meta[ $meta_key ] ?? null;
	}

	/**
	 * @return array
	 */
	public function getMetaFields(): array {
		return $this->post_meta;
	}
}
