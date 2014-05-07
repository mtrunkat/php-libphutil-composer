<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilRemarkupRuleDel
  extends \Facebook\Libphutil\PhutilRemarkupRule {

  public function getPriority() {
    return 1000.0;
  }

  public function apply($text) {
    if ($this->getEngine()->isTextMode()) {
      return $text;
    }

    return $this->replaceHTML(
      '@(?<!~)~~([^\s~].*?~*)~~@s',
      array($this, 'applyCallback'),
      $text);
  }

  protected function applyCallback($matches) {
    return \Facebook\Libphutil\Functions\render::hsprintf('<del>%s</del>', $matches[1]);
  }

}
