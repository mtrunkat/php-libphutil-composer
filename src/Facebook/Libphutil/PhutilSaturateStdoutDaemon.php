<?php

namespace Facebook\Libphutil;

/**
 * Daemon which dumps huge amounts of data to stdout.
 *
 * @group testcase
 */
class PhutilSaturateStdoutDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    for ($ii = 0; $ii < 512; $ii++) {
      echo str_repeat('~', 1024 * 1024);
    }
  }

}
