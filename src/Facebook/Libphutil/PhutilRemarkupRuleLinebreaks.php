<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupRuleLinebreaks
  extends \Facebook\Libphutil\PhutilRemarkupRule {

  public function apply($text) {
    if ($this->getEngine()->isTextMode()) {
      return $text;
    }

    return \Facebook\Libphutil\Functions\render::phutil_escape_html_newlines($text);
  }

}
