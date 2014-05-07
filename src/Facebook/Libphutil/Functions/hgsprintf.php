<?php

namespace Facebook\Libphutil\Functions;

class hgsprintf {
  
  /**
   * Format a Mercurial revset expression. Supports the following conversions:
   *
   *  %s Symbol
   *    Escapes a Mercurial symbol, like a branch or bookmark name.
   *
   *  %R Rrraw Rreferrrence / Rrrrevset
   *    Passes text through unescaped (e.g., an already-escaped revset).
   *
   * @group mercurial
   */
  static function hgsprintf($pattern /* , ... */) {
    $args = func_get_args();
    return \Facebook\Libphutil\Functions\xsprintf::xsprintf('\Facebook\Libphutil\Functions\hgsprintf::xsprintf_mercurial', null, $args);
  }
  
  
  /**
   * \Facebook\Libphutil\Functions\xsprintf::xsprintf() callback for Mercurial encoding.
   *
   * @group mercurial
   */
  static function xsprintf_mercurial($userdata, &$pattern, &$pos, &$value, &$length) {
  
    $type = $pattern[$pos];
  
    switch ($type) {
      case 's':
        $value = "'".addcslashes($value, "'\\")."'";
        break;
      case 'R':
        $type = 's';
        break;
    }
  
    $pattern[$pos]  = $type;
  }
  
}
