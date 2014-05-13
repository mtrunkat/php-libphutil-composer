<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupEngineRemarkupLiteralBlockRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;
    if (preg_match("/^%%%/", $lines[$cursor])) {
      $num_lines++;

      while (isset($lines[$cursor])) {
        if (!preg_match("/%%%\s*$/", $lines[$cursor])) {
          $num_lines++;
          $cursor++;
          continue;
        }
        break;
      }
    }

    return $num_lines;
  }

  public function markupText($text, $children) {
    $text = preg_replace('/%%%\s*$/', '', substr($text, 3));
    if ($this->getEngine()->isTextMode()) {
      return $text;
    }

    $text = \Facebook\Libphutil\Functions\utils::phutil_split_lines($text, $retain_endings = true);
    return \Facebook\Libphutil\Functions\render::phutil_implode_html(\Facebook\Libphutil\Functions\render::phutil_tag('br', array()), $text);
  }
}
