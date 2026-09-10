<?php

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID = 0;
		public $post_title = '';
		public $post_status = 'draft';
		public $post_content = '';
		public $post_content_filtered = '';
		public $post_type = 'post';

		public function __construct( array $data = [] ) {
			foreach ( $data as $property => $value ) {
				$this->{$property} = $value;
			}
		}
	}
}
