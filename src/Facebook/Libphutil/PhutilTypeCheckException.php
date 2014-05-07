<?php

namespace Facebook\Libphutil;

final class PhutilTypeCheckException extends \Exception {

  public function __construct(
    \Facebook\Libphutil\PhutilTypeSpec $type,
    $value,
    $name = null,
    $err = null) {

    if ($name !== null) {
      $invalid = \Facebook\Libphutil\Functions\pht::pht(
        "Parameter '%s' has invalid type.",
        $name);
    } else {
      $invalid = \Facebook\Libphutil\Functions\pht::pht("Parameter has invalid type.");
    }

    if ($type->getType() == 'regex') {
      if (is_string($value)) {
        $message = \Facebook\Libphutil\Functions\pht::pht(
          "Expected a regular expression, but '%s' is not valid: %s",
          $value,
          $err);
      } else {
        $message = \Facebook\Libphutil\Functions\pht::pht(
          "Expected a regular expression, but value is not valid: %s",
          $err);
      }
    } else {
      $message = \Facebook\Libphutil\Functions\pht::pht(
        "Expected type '%s', got type '%s'.",
        $type->toString(),
        \Facebook\Libphutil\PhutilTypeSpec::getTypeOf($value));
    }

    parent::__construct($invalid.' '.$message);
  }

}
