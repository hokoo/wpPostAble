<?php
namespace iTRON\wpPostAble\Exceptions;

use iTRON\wpPostAble\wpPostAble;
use Throwable;

class wppaParamException extends wppaException {
	public const OPERATION_READ = 'read';
	public const OPERATION_WRITE = 'write';

	public const REASON_INVALID_JSON = 'invalid_json';
	public const REASON_INVALID_ROOT = 'invalid_root';
	public const REASON_ENCODE_FAILED = 'encode_failed';

	private $param_name;
	private $operation;
	private $reason;
	private $json_error_code;

	function __construct(
		wpPostAble $postable,
		string $param_name,
		string $operation,
		string $reason,
		int $json_error_code,
		$message = "",
		?Throwable $previous = null
	) {
		parent::__construct( $postable, $message, $json_error_code, $previous );
		$this->param_name = $param_name;
		$this->operation = $operation;
		$this->reason = $reason;
		$this->json_error_code = $json_error_code;
	}

	public function getParamName(): string {
		return $this->param_name;
	}

	public function getOperation(): string {
		return $this->operation;
	}

	public function getReason(): string {
		return $this->reason;
	}

	public function getJsonErrorCode(): int {
		return $this->json_error_code;
	}
}
