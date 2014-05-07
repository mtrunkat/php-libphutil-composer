<?php

namespace Facebook\Libphutil;

/**
 * Daemon which hangs immediately.
 *
 * @group testcase
 */
final class PhutilHangForeverDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    while (true) {
      sleep(60);
    }
  }

}
