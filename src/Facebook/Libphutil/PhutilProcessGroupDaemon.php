<?php

namespace Facebook\Libphutil;

/**
 * Daemon which spawns nonterminating, death-resistant children.
 *
 * @group testcase
 */
final class PhutilProcessGroupDaemon extends \Facebook\Libphutil\PhutilTortureTestDaemon {

  public function run() {
    $root = \Facebook\Libphutil\Functions\moduleutils::phutil_get_library_root('phutil');
    $root = dirname($root);

    \Facebook\Libphutil\Functions\execx::execx('%s', $root.'/scripts/daemon/torture/resist-death.php');
  }

}
