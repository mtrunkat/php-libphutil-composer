<?php

namespace Facebook\Libphutil\Functions;

class moduleutils {
  
  static function phutil_get_library_root($library) {
    $bootloader = \Facebook\Libphutil\PhutilBootloader::getInstance();
    return $bootloader->getLibraryRoot($library);
  }
  
  
  static function phutil_get_library_root_for_path($path) {
    foreach (\Facebook\Libphutil\Filesystem::walkToRoot($path) as $dir) {
      if (@file_exists($dir.'/__phutil_library_init__.php')) {
        return $dir;
      }
    }
    return null;
  }
  
  static function phutil_get_library_name_for_root($path) {
    $path = rtrim(\Facebook\Libphutil\Filesystem::resolvePath($path), '/');
  
    $bootloader = \Facebook\Libphutil\PhutilBootloader::getInstance();
    $libraries = $bootloader->getAllLibraries();
    foreach ($libraries as $library) {
      $root = $bootloader->getLibraryRoot($library);
      if (rtrim(\Facebook\Libphutil\Filesystem::resolvePath($root), '/') == $path) {
        return $library;
      }
    }
  
    return null;
  }
  
  /**
   * Warns about use of deprecated behavior.
   */
  static function phutil_deprecated($what, $why) {
    \Facebook\Libphutil\PhutilErrorHandler::dispatchErrorMessage(
      \Facebook\Libphutil\PhutilErrorHandler::DEPRECATED,
      $what,
      array(
        'why' => $why,
      ));
  }
  
}
