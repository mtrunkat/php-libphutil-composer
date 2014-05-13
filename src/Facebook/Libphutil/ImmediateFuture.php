<?php

namespace Facebook\Libphutil;

/**
 * Degenerate future which returns an already-existing result without performing
 * any computation.
 *
 * @group futures
 */
class ImmediateFuture extends \Facebook\Libphutil\Future {

  public function __construct($result) {
    $this->result = $result;
  }

  public function isReady() {
    return true;
  }

}
