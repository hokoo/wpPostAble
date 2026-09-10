<?php
/**
 * Use this trait in conjunction wpPostAble interface only.
 *
 * By using this trait, you should call wpPostAble( $post_type, $post_id ) method
 * in the beginning __construct() of your class.
 * Pass to it two parameters
 *      $post_type      string      WP post type, associated with your class
 *      $post_id        int|WP_Post|null  Existing post or ID, or nothing for creating a new post
 */

namespace iTRON\wpPostAble;

use iTRON\wpPostAble\Exceptions\wppaCreatePostException;
use iTRON\wpPostAble\Exceptions\wppaDeletePostException;
use iTRON\wpPostAble\Exceptions\wppaLoadPostException;
use iTRON\wpPostAble\Exceptions\wppaParamException;
use iTRON\wpPostAble\Exceptions\wppaSavePostException;
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
	 * @var array
	 */
	private $post_meta = [];

	/**
	 * @var array
	 */
	private $dirty_post_meta = [];

	/**
	 * Call this method in the beginning __construct() of your class.
	 *
	 * @param string           $post_type
	 * @param int|WP_Post|null $post_id
	 *
	 * @return $this
	 * @throws wppaCreatePostException
	 * @throws wppaLoadPostException
	 */
	private function wpPostAble( string $post_type, $post_id = 0 ): self {

		if ( ! is_int( $post_id ) && null !== $post_id && ! ( $post_id instanceof WP_Post ) ) {
			$given_type = is_object( $post_id ) ? get_class( $post_id ) : gettype( $post_id );
			throw new \TypeError(
				sprintf(
					'%s(): Argument #2 ($post_id) must be of type int|WP_Post|null, %s given',
					__METHOD__,
					$given_type
				)
			);
		}

		if ( $this->post instanceof WP_Post ) return $this;

		$this->post_type = $post_type;

		if ( $post_id instanceof WP_Post ) {
			return $this->loadPostObject( $post_id );
		}

		if ( empty( $post_id ) ){
			$post_id = wp_insert_post([
				'post_type'     => $this->getPostType(),
				'post_status'   => $this->applyFilters( '\wpPostAbleTrait\init\defaultStatus', 'draft' ),
				'post_title'    => $this->applyFilters( '\wpPostAbleTrait\init\defaultTitle', 'draft' ),
				'post_content'  => $this->applyFilters( '\wpPostAbleTrait\init\defaultContent', 'Empty.' ),
			], true );

			if ( empty( $post_id ) || is_wp_error( $post_id ) ){
				$error = empty( $post_id ) ? new WP_Error() : $post_id;
				/** @var wpPostAble $this */
				throw new wppaCreatePostException( $this, $error, $error->get_error_message() );
			}
		}

		return $this->loadPost( $post_id );
	}

	private function applyFilters( string $filterName, ...$data ){
		array_push( $data, __CLASS__ );

		$wideFilter = apply_filters( $filterName, ...$data );

		return apply_filters( __CLASS__ . $filterName, $wideFilter, ...$data );
	}

	private function doAction( string $actionName, ...$data ){
		array_push( $data, __CLASS__ );

		do_action( $actionName, ...$data );

		do_action( __CLASS__ . $actionName, ...$data );
	}

	private function doActionRef( string $actionName, $data ){
		array_push( $data, __CLASS__ );

		do_action( $actionName, ...$data );

		do_action( __CLASS__ . $actionName, ...$data );
	}

	/**
	 * @throws wppaParamException
	 */
	public function getParam( string $param ) {
		$data = $this->decodeParamMap( $param, wppaParamException::OPERATION_READ );
		return $data->{$param} ?? null;
	}

	/**
	 * @throws wppaParamException
	 */
	public function setParam( string $param, $value ) {
		$data = $this->decodeParamMap( $param, wppaParamException::OPERATION_WRITE );
		$data->{$param} = $value;

		try {
			$encoded = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		} catch ( \Throwable $previous ) {
			throw new wppaParamException(
				$this,
				$param,
				wppaParamException::OPERATION_WRITE,
				wppaParamException::REASON_ENCODE_FAILED,
				JSON_ERROR_NONE,
				"Cannot write parameter \"$param\": JSON encoding failed.",
				$previous
			);
		}

		$json_error_code = json_last_error();
		if ( false === $encoded || JSON_ERROR_NONE !== $json_error_code ) {
			throw new wppaParamException(
				$this,
				$param,
				wppaParamException::OPERATION_WRITE,
				wppaParamException::REASON_ENCODE_FAILED,
				$json_error_code,
				"Cannot write parameter \"$param\": JSON encoding failed (" . json_last_error_msg() . ').'
			);
		}

		$this->post->post_content_filtered = $encoded;
	}

	/**
	 * @throws wppaParamException
	 */
	private function decodeParamMap( string $param, string $operation ): \stdClass {
		$content = $this->post->post_content_filtered;
		if ( '' === $content ) {
			return new \stdClass();
		}

		$data = json_decode( $content );
		$json_error_code = json_last_error();
		if ( JSON_ERROR_NONE !== $json_error_code ) {
			throw new wppaParamException(
				$this,
				$param,
				$operation,
				wppaParamException::REASON_INVALID_JSON,
				$json_error_code,
				"Cannot $operation parameter \"$param\": invalid parameter JSON (" . json_last_error_msg() . ').'
			);
		}

		if ( $data instanceof \stdClass ) {
			return $data;
		}

		if ( is_array( $data ) ) {
			$map = new \stdClass();
			foreach ( $data as $key => $value ) {
				$map->{(string) $key} = $value;
			}
			return $map;
		}

		throw new wppaParamException(
			$this,
			$param,
			$operation,
			wppaParamException::REASON_INVALID_ROOT,
			JSON_ERROR_NONE,
			"Cannot $operation parameter \"$param\": parameter JSON root must be an object or array."
		);
	}

	/**
	 * @throws wppaDeletePostException
	 */
	public function deletePost(){
		$post = $this->post;
		$post_id = $post->ID;

		$this->doAction( '\wpPostAbleTrait\deletePost\beforeDeletePost', $post_id, $post );

		if ( ! wp_delete_post( $post_id ) instanceof WP_Post ) {
			$error = new WP_Error(
				'delete_post_failed',
				"Unable to delete post [ $post_id ].",
				[ 'post_id' => $post_id ]
			);
			/** @var wpPostAble $this */
			throw new wppaDeletePostException( $this, $post, $error, $error->get_error_message() );
		}

		$this->post = null;

		$this->doAction( '\wpPostAbleTrait\deletePost\afterDeletePost', $post_id, $post );
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
		if ( ! empty( $this->dirty_post_meta ) ) {
			$postData[ 'meta_input' ] = $this->dirty_post_meta;
		}
		$result = wp_update_post( $postData, true );
		if ( empty( $result ) || is_wp_error( $result ) ){
			$error = empty( $result ) ? new WP_Error() : $result;
			/** @var wpPostAble $this */
			throw new wppaSavePostException( $this, $error, $error->get_error_message() );
		}
		$this->dirty_post_meta = [];
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

		return $this->loadPostObject( $post );
	}

	/**
	 * Load an already-resolved WordPress post into the model.
	 *
	 * @param WP_Post $post Post to load.
	 * @return $this
	 * @throws wppaLoadPostException
	 */
	private function loadPostObject( WP_Post $post ): self {
		$post_id = $post->ID;

		if (
			! $this->applyFilters( '\wpPostAbleTrait\loadPost\equalPostType', $post->post_type === $this->post_type )
		){
			/** @var wpPostAble $this */
			throw new wppaLoadPostException( $post_id, $this,
				"Incompatible post type. Class type is \"$this->post_type\", trying to load \"$post->post_type\""
			);
		}

		$this->post = $post;

		if ( $this->applyFilters( '\wpPostAbleTrait\loadPost\loadMeta', true, $this ) ) {
            $post_meta = get_post_meta( $this->post->ID, '', true );
            $this->post_meta = array_combine( array_keys( $post_meta ), array_column( $post_meta, 0 ) );

			// Since get_metadata_raw() does not deserialize meta values if the $key not specified, we should do it manually.
			array_walk(
				$this->post_meta,
				function ( & $value ) {
					$value = maybe_unserialize( $value );
				}
			);
		}

		$this->doActionRef( '\wpPostAbleTrait\loadPost\loading', [ & $this ] );
		return $this;
	}

	public function getTitle(): string{
		return $this->post->post_title;
	}

	public function setTitle( string $title ): self {
		$this->post->post_title = $title;
		return $this;
	}

	public function getSlug(): string{
		return $this->post->post_name;
	}

	public function setSlug( string $slug ): self {
		$this->post->post_name = $slug;
		return $this;
	}

	public function getMenuOrder(): int{
		return (int) $this->post->menu_order;
	}

	public function setMenuOrder( int $menuOrder ): self {
		$this->post->menu_order = $menuOrder;
		return $this;
	}

	public function getStatus(): string{
		return $this->post->post_status;
	}

	public function setStatus( string $status ): self {
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
		$this->dirty_post_meta[ $meta_key ] = $meta_value;
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
