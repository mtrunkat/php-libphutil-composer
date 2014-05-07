<?php

namespace Facebook\Libphutil\Functions;

class xhpast_parse {
  
  /**
   * @group xhpast
   */
  static function xhpast_is_available() {
    static $available;
    if ($available === null) {
      $available = false;
      $bin = \Facebook\Libphutil\Functions\xhpast_parse::xhpast_get_binary_path();
      if (\Facebook\Libphutil\Filesystem::pathExists($bin)) {
        list($err, $stdout) = \Facebook\Libphutil\Functions\execx::exec_manual('%s --version', $bin);
        if (!$err) {
          $version = trim($stdout);
          if ($version === "xhpast version 5.5.8/1e") {
            $available = true;
          }
        }
      }
    }
    return $available;
  }
  
  
  /**
   * @group xhpast
   */
  static function xhpast_get_binary_path() {
    if (\Facebook\Libphutil\Functions\utils::phutil_is_windows()) {
      return dirname(__FILE__).'\\xhpast.exe';
    }
    return dirname(__FILE__).'/xhpast';
  }
  
  
  /**
   * @group xhpast
   */
  static function xhpast_get_build_instructions() {
    $root = \Facebook\Libphutil\Functions\moduleutils::phutil_get_library_root('phutil');
    $make = $root.'/../scripts/build_xhpast.sh';
    $make = \Facebook\Libphutil\Filesystem::resolvePath($make);
    return <<<EOHELP
  Your version of 'xhpast' is unbuilt or out of date. Run this script to build it:
  
    \$ {$make}
  
  EOHELP;
  }
  
  
  /**
   * @group xhpast
   */
  static function xhpast_get_parser_future($data) {
    if (!\Facebook\Libphutil\Functions\xhpast_parse::xhpast_is_available()) {
      throw new \Exception(\Facebook\Libphutil\Functions\xhpast_parse::xhpast_get_build_instructions());
    }
    $future = new \Facebook\Libphutil\ExecFuture('%s', \Facebook\Libphutil\Functions\xhpast_parse::xhpast_get_binary_path());
    $future->write($data);
  
    return $future;
  }
  
}
