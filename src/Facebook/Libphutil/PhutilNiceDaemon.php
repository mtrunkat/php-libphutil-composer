<?php

namespace Facebook\Libphutil;

/**
 * Daemon which behaves properly.
 *
 * @group testcase
 */
class PhutilNiceDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    while (true) {
      $this->log(date('r'));
      $this->stillWorking();
      sleep(1);
    }
  }

}
