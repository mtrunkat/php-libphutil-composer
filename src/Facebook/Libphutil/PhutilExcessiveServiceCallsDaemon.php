<?php

namespace Facebook\Libphutil;

/**
 * Daemon which makes a lot of service calls.
 *
 * @group testcase
 */
class PhutilExcessiveServiceCallsDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    while (true) {
      \Facebook\Libphutil\Functions\execx::execx('true');
      $this->stillWorking();
    }
  }

}
