<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupRuleItalic
  extends \Facebook\Libphutil\PhutilRemarkupRule {

  public function getPriority() {
    return 1000.0;
  }

  public function apply($text) {
    if ($this->getEngine()->isTextMode()) {
      return $text;
    }

    return $this->replaceHTML(
      '@(?<!:)//(.+?)//@s',
      array($this, 'applyCallback'),
      $text);
  }

  protected function applyCallback($matches) {
    return \Facebook\Libphutil\Functions\render::hsprintf('<em>%s</em>', $matches[1]);
  }

}
