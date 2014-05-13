<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupEngineRemarkupDefaultBlockRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  public function getPriority() {
    return 750;
  }

  public function getMatchingLineCount(array $lines, $cursor) {
    return 1;
  }

  public function markupText($text, $children) {
    $text = trim($text);
    $text = $this->applyRules($text);

    if ($this->getEngine()->isTextMode()) {
      if (!$this->getEngine()->getConfig('preserve-linebreaks')) {
        $text = preg_replace('/ *\n */', ' ', $text);
      }
      return $text;
    }

    if ($this->getEngine()->getConfig('preserve-linebreaks')) {
      $text = \Facebook\Libphutil\Functions\render::phutil_escape_html_newlines($text);
    }

    if (!strlen($text)) {
      return null;
    }

    return \Facebook\Libphutil\Functions\render::phutil_tag('p', array(), $text);
  }

}
