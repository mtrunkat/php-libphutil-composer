<?php

namespace Facebook\Libphutil\Functions;

class urisprintf {
  
  /**
   * Format a URI. This function behaves like sprintf(), except that all the
   * normal conversions (like %s) will be properly escaped, and additional
   * conversions are supported:
   *
   *   %s (String)
   *     Escapes text for use in a URI.
   *
   *   %p (Path Component)
   *     Escapes text for use in a URI path component.
   *
   *   %R (Raw String)
   *     Inserts raw, unescaped text. DANGEROUS!
   */
  static function urisprintf($pattern /* , ... */) {
    $args = func_get_args();
    return \Facebook\Libphutil\Functions\xsprintf::xsprintf('\Facebook\Libphutil\Functions\urisprintf::xsprintf_uri', null, $args);
  }
  
  static function vurisprintf($pattern, array $argv) {
    array_unshift($argv, $pattern);
    return call_user_func_array('\Facebook\Libphutil\Functions\urisprintf::urisprintf', $argv);
  }
  
  /**
   * uri_sprintf() callback for URI encoding.
   * @group markup
   */
  static function xsprintf_uri($userdata, &$pattern, &$pos, &$value, &$length) {
  
    $type = $pattern[$pos];
  
    switch ($type) {
      case 's':
        $value = \Facebook\Libphutil\Functions\render::phutil_escape_uri($value);
        $type = 's';
        break;
  
      case 'p':
        $value = \Facebook\Libphutil\Functions\render::phutil_escape_uri_path_component($value);
        $type = 's';
        break;
  
      case 'R':
        $type = 's';
        break;
    }
  
    $pattern[$pos]  = $type;
  }
  
}
