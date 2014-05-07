<?php

namespace Facebook\Libphutil\Functions;

class functions {
  
  /**
   * Convenience function for instantiating a new @{class:FutureIterator}.
   *
   * @param list              List of @{class:Future}s.
   * @return \Facebook\Libphutil\FutureIterator   New @{class:FutureIterator} over those futures.
   * @group futures
   */
  static function Futures($futures) {
    return new \Facebook\Libphutil\FutureIterator($futures);
  }
  
}
