<?php

namespace Facebook\Libphutil;

/**
 * Daemon which fails immediately.
 *
 * @group testcase
 */
class PhutilFatalDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    exit(1);
  }

}
