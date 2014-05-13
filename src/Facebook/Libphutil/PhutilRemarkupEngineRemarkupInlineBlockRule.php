<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupEngineRemarkupInlineBlockRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    return 1;
  }

  public function markupText($text, $children) {
    return $this->applyRules($text);
  }

}
