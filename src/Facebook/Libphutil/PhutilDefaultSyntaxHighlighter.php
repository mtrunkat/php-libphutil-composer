<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilDefaultSyntaxHighlighter {

  public function setConfig($key, $value) {
    return $this;
  }

  public function getHighlightFuture($source) {
    $result = \Facebook\Libphutil\Functions\render::hsprintf('%s', $source);
    return new \Facebook\Libphutil\ImmediateFuture($result);
  }
}
