<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilRemarkupEngineRemarkupInterpreterRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  const START_BLOCK_PATTERN = '/^([\w]+)\s*(?:\(([^)]+)\)\s*)?{{{/';
  const END_BLOCK_PATTERN = '/}}}\s*$/';

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;

    if (preg_match(self::START_BLOCK_PATTERN, $lines[$cursor])) {
      $num_lines++;

      while (isset($lines[$cursor])) {
        if (preg_match(self::END_BLOCK_PATTERN, $lines[$cursor])) {
          break;
        }
        $num_lines++;
        $cursor++;
      }
    }

    return $num_lines;
  }

  public function markupText($text, $children) {

    $lines = explode("\n", $text);
    $first_key = \Facebook\Libphutil\Functions\utils::head_key($lines);
    $last_key = \Facebook\Libphutil\Functions\utils::last_key($lines);
    while (trim($lines[$last_key]) === '') {
      unset($lines[$last_key]);
      $last_key = \Facebook\Libphutil\Functions\utils::last_key($lines);
    }
    $matches = null;

    preg_match(self::START_BLOCK_PATTERN, \Facebook\Libphutil\Functions\utils::head($lines), $matches);

    $argv = array();
    if (isset($matches[2])) {
      $argv = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilSimpleOptions())->parse($matches[2]);
    }

    $interpreters = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilSymbolLoader())
      ->setAncestorClass('\Facebook\Libphutil\PhutilRemarkupBlockInterpreter')
      ->loadObjects();

    foreach ($interpreters as $interpreter) {
      $interpreter->setEngine($this->getEngine());
    }

    $lines[$first_key] = preg_replace(
      self::START_BLOCK_PATTERN,
      "",
      $lines[$first_key]);
    $lines[$last_key] = preg_replace(
      self::END_BLOCK_PATTERN,
      "",
      $lines[$last_key]);

    if (trim($lines[$first_key]) === '') {
      unset($lines[$first_key]);
    }
    if (trim($lines[$last_key]) === '') {
      unset($lines[$last_key]);
    }

    $content = implode("\n", $lines);

    $interpreters = \Facebook\Libphutil\Functions\utils::mpull($interpreters, null, 'getInterpreterName');

    if (isset($interpreters[$matches[1]])) {
      return $interpreters[$matches[1]]->markupContent($content, $argv);
    }

    $message = \Facebook\Libphutil\Functions\pht::pht('No interpreter found: %s', $matches[1]);

    if ($this->getEngine()->isTextMode()) {
      return '('.$message.')';
    }

    return \Facebook\Libphutil\Functions\render::phutil_tag(
      'div',
      array(
        'class' => 'remarkup-interpreter-error',
      ),
      $message);
  }

}
