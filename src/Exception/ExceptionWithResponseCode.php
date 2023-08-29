<?php

namespace Drupal\aesirx_analytics\Exception;

use Exception;
use Throwable;

class ExceptionWithResponseCode extends Exception {

    private int $responseCode;
    public function __construct(string $message, int $responseCode, int $code = 0, Throwable $previous = null ) {
        $this->responseCode = $responseCode;
        parent::__construct( $message, $code, $previous );
    }

    public function getResponseCode(): int {
        return $this->responseCode;
    }
}
