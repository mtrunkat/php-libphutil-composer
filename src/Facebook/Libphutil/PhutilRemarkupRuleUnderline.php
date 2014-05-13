<?php

namespace Facebook\Libphutil;

class PhutilRemarkupRuleUnderline
  extends \Facebook\Libphutil\PhutilRemarkupRule {

  public function getPriority() {
    return 1000.0;
  }

  public function apply($text) {
    if ($this->getEngine()->isTextMode()) {
      return $text;
    }

    return $this->replaceHTML(
      '@(?<!_|/)__([^\s_/].*?_*)__(?!/|\.\S)@s',
      array($this, 'applyCallback'),
      $text);
  }

  protected function applyCallback($matches) {
    return \Facebook\Libphutil\Functions\render::hsprintf('<u>%s</u>', $matches[1]);
  }

}
