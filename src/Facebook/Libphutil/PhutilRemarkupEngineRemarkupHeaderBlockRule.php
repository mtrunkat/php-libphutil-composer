<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilRemarkupEngineRemarkupHeaderBlockRule
  extends \Facebook\Libphutil\PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;
    if (preg_match('/^(={1,5}).*+$/', $lines[$cursor])) {
      $num_lines = 1;
    } else {
      if (isset($lines[$cursor + 1])) {
        $line = $lines[$cursor] . $lines[$cursor + 1];
        if (preg_match('/^([^\n]+)\n[-=]{2,}\s*$/', $line)) {
          $num_lines = 2;
          $cursor++;
        }
      }
    }

    if ($num_lines) {
      $cursor++;
      while (isset($lines[$cursor]) && !strlen(trim($lines[$cursor]))) {
        $num_lines++;
        $cursor++;
      }
    }

    return $num_lines;
  }

  const KEY_HEADER_TOC = 'headers.toc';

  public function markupText($text, $children) {
    $text = trim($text);

    $lines = \Facebook\Libphutil\Functions\utils::phutil_split_lines($text);
    if (count($lines) > 1) {
      $level = ($lines[1][0] == '=') ? 1 : 2;
      $text = trim($lines[0]);
    } else {
      $level = 0;
      for ($ii = 0; $ii < min(5, strlen($text)); $ii++) {
        if ($text[$ii] == '=') {
          ++$level;
        } else {
          break;
        }
      }
      $text = trim($text, ' =');
    }

    $engine = $this->getEngine();

    if ($engine->isTextMode()) {
      $char = ($level == 1) ? '=' : '-';
      return $text."\n".str_repeat($char, \Facebook\Libphutil\Functions\utf8::phutil_utf8_strlen($text));
    }

    $use_anchors = $engine->getConfig('header.generate-toc');

    $anchor = null;
    if ($use_anchors) {
      $anchor = $this->generateAnchor($level, $text);
    }

    $text = \Facebook\Libphutil\Functions\render::phutil_tag(
      'h'.($level + 1),
      array(),
      array($anchor, $this->applyRules($text)));

    return $text;
  }

  private function generateAnchor($level, $text) {
    $anchor = strtolower($text);
    $anchor = preg_replace('/[^a-z0-9]/', '-', $anchor);
    $anchor = preg_replace('/--+/', '-', $anchor);
    $anchor = trim($anchor, '-');
    $anchor = substr($anchor, 0, 24);
    $anchor = trim($anchor, '-');
    $base = $anchor;

    $key = self::KEY_HEADER_TOC;
    $engine = $this->getEngine();
    $anchors = $engine->getTextMetadata($key, array());

    $suffix = 1;
    while (!strlen($anchor) || isset($anchors[$anchor])) {
      $anchor = $base.'-'.$suffix;
      $anchor = trim($anchor, '-');
      $suffix++;
    }

    // When a document contains a link inside a header, like this:
    //
    //  = [[ http://wwww.example.com/ | example ]] =
    //
    // ...we want to generate a TOC entry with just "example", but link the
    // header itself. We push the 'toc' state so all the link rules generate
    // just names.
    $engine->pushState('toc');
      $text = $this->applyRules($text);
      $text = $engine->restoreText($text);

      $anchors[$anchor] = array($level, $text);
    $engine->popState('toc');

    $engine->setTextMetadata($key, $anchors);

    return \Facebook\Libphutil\Functions\render::phutil_tag(
      'a',
      array(
        'name' => $anchor,
      ),
      '');
  }

  public static function renderTableOfContents(\Facebook\Libphutil\PhutilRemarkupEngine $engine) {

    $key = self::KEY_HEADER_TOC;
    $anchors = $engine->getTextMetadata($key, array());

    if (count($anchors) < 2) {
      // Don't generate a TOC if there are no headers, or if there's only
      // one header (since such a TOC would be silly).
      return null;
    }

    $depth = 0;
    $toc = array();
    foreach ($anchors as $anchor => $info) {
      list($level, $name) = $info;

      while ($depth < $level) {
        $toc[] = \Facebook\Libphutil\Functions\render::hsprintf('<ul>');
        $depth++;
      }
      while ($depth > $level) {
        $toc[] = \Facebook\Libphutil\Functions\render::hsprintf('</ul>');
        $depth--;
      }

      $toc[] = \Facebook\Libphutil\Functions\render::phutil_tag(
        'li',
        array(),
        \Facebook\Libphutil\Functions\render::phutil_tag(
          'a',
          array(
            'href' => '#'.$anchor,
          ),
          $name));
    }
    while ($depth > 0) {
      $toc[] = \Facebook\Libphutil\Functions\render::hsprintf('</ul>');
      $depth--;
    }

    return \Facebook\Libphutil\Functions\render::phutil_implode_html("\n", $toc);
  }

}
