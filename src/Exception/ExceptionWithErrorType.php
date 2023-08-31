<?php

namespace Drupal\aesirx_analytics\Exception;

use Exception;
use Throwable;

class ExceptionWithErrorType extends Exception {

  private ?string $errorType;

  public function __construct(string $message = "", ?string $errorType = null, int $code = 0, ?Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->errorType = $errorType;
  }

  public function getErrorType(): ?string {
    return $this->errorType;
  }

}
