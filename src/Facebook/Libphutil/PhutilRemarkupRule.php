<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 * @stable
 */
abstract class PhutilRemarkupRule {

  private $engine;
  private $replaceCallback;

  public function setEngine(\Facebook\Libphutil\PhutilRemarkupEngine $engine) {
    $this->engine = $engine;
    return $this;
  }

  public function getEngine() {
    return $this->engine;
  }

  public function getPriority() {
    return 500.0;
  }

  abstract public function apply($text);

  public function getPostprocessKey() {
    return spl_object_hash($this);
  }

  public function didMarkupText() {
    return;
  }

  protected function replaceHTML($pattern, $callback, $text) {
    $this->replaceCallback = $callback;
    return \Facebook\Libphutil\Functions\render::phutil_safe_html(preg_replace_callback(
      $pattern,
      array($this, 'replaceHTMLCallback'),
      \Facebook\Libphutil\Functions\render::phutil_escape_html($text)));
  }

  private function replaceHTMLCallback($match) {
    return \Facebook\Libphutil\Functions\render::phutil_escape_html(call_user_func(
      $this->replaceCallback,
      array_map('\Facebook\Libphutil\Functions\render::phutil_safe_html', $match)));
  }

}
