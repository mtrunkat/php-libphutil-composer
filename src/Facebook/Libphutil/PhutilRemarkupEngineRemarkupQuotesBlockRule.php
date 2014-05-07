<?php

namespace Facebook\Libphutil;

final class PhutilRemarkupEngineRemarkupQuotesBlockRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $pos = $cursor;

    if (preg_match('/^>/', $lines[$pos])) {
      do {
        ++$pos;
      } while (isset($lines[$pos]) && preg_match('/^>/', $lines[$pos]));
    }

    return ($pos - $cursor);
  }

  public function supportsChildBlocks() {
    return true;
  }

  public function extractChildText($text) {
    $text = \Facebook\Libphutil\Functions\utils::phutil_split_lines($text, true);
    foreach ($text as $key => $line) {
      $text[$key] = substr($line, 1);
    }

    return array('', implode('', $text));
  }

  public function markupText($text, $children) {
    if ($this->getEngine()->isTextMode()) {
      $lines = \Facebook\Libphutil\Functions\utils::phutil_split_lines($children);
      return '> '.implode("\n> ", $lines);
    }

    return \Facebook\Libphutil\Functions\render::phutil_tag(
      'blockquote',
      array(),
      $children);
  }

}
