<?php

namespace Facebook\Libphutil;

class PhutilSafeHTML {

  private $content;

  public function __construct($content) {
    $this->content = (string)$content;
  }

  public function __toString() {
    return $this->content;
  }

  public function getHTMLContent() {
    return $this->content;
  }

  public function appendHTML($html /* , ... */) {
    foreach (func_get_args() as $html) {
      $this->content .= \Facebook\Libphutil\Functions\render::phutil_escape_html($html);
    }
    return $this;
  }

  public static function applyFunction($function, $string /* , ... */) {
    $args = func_get_args();
    array_shift($args);
    $args = array_map('\Facebook\Libphutil\Functions\render::phutil_escape_html', $args);
    return new \Facebook\Libphutil\PhutilSafeHTML(call_user_func_array($function, $args));
  }

// Requires http://pecl.php.net/operator.

  public function __concat($html) {
    $clone = clone $this;
    return $clone->appendHTML($html);
  }

  public function __assign_concat($html) {
    return $this->appendHTML($html);
  }

}
