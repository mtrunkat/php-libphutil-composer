<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilRemarkupRuleEscapeRemarkup
  extends \Facebook\Libphutil\PhutilRemarkupRule {

  public function getPriority() {
    return 0;
  }

  public function apply($text) {
    if (strpos($text, "\1") === false) {
      return $text;
    }

    $replace = $this->getEngine()->storeText("\1");

    return str_replace("\1", $replace, $text);
  }

}
