<?php

namespace Facebook\Libphutil;

/**
 * @group aws
 */
class PhutilAWSException extends \Exception {

  private $httpStatus;
  private $requestID;

  public function __construct($http_status, array $params) {
    $this->httpStatus = $http_status;
    $this->requestID = \Facebook\Libphutil\Functions\utils::idx($params, 'RequestID');

    $this->params = $params;

    $desc = array();
    $desc[] = 'AWS Request Failed';
    $desc[] = 'HTTP Status Code: '.$http_status;

    if ($this->requestID) {
      $desc[] = 'AWS Request ID: '.$this->requestID;
      $errors = \Facebook\Libphutil\Functions\utils::idx($params, 'Errors');
      if ($errors) {
        $desc[] = 'AWS Errors:';
        foreach ($errors as $error) {
          list($code, $message) = $error;
          $desc[] = "    - {$code}: {$message}\n";
        }
      }
    } else {
      $desc[] = 'Response Body: '.\Facebook\Libphutil\Functions\utils::idx($params, 'body');
    }

    $desc = implode("\n", $desc);

    parent::__construct($desc);
  }

  public function getRequestID() {
    return $this->requestID;
  }

  public function getHTTPStatus() {
    return $this->httpStatus;
  }

}
