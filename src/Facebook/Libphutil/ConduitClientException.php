<?php

namespace Facebook\Libphutil;

/**
 * @group conduit
 */
class ConduitClientException extends \Exception {

  protected $errorCode;

  public function __construct($code, $info) {
    parent::__construct("{$code}: {$info}");
    $this->errorCode = $code;
  }

  public function getErrorCode() {
    return $this->errorCode;
  }

}
