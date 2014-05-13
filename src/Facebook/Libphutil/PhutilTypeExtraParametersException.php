<?php

namespace Facebook\Libphutil;

class PhutilTypeExtraParametersException extends \Exception {

  private $parameters;

  public function getParameters() {
    return $this->parameters;
  }

  public function __construct(array $extra) {
    $message = \Facebook\Libphutil\Functions\pht::pht(
      'Got unexpected parameters: %s',
      implode(', ', array_keys($extra)));

    parent::__construct($message);

    $this->parameters = $extra;
  }

}
