<?php

namespace Facebook\Libphutil;

/**
 * Highlights source code with a rainbow of colors, regardless of the language.
 * This highlighter is useless, absurd, and extremely slow.
 *
 * @group markup
 */
class PhutilRainbowSyntaxHighlighter {

  private $config = array();

  public function setConfig($key, $value) {
    $this->config[$key] = $value;
    return $this;
  }

  public function getHighlightFuture($source) {

    $color = 0;
    $colors = array(
      'rbw_r',
      'rbw_o',
      'rbw_y',
      'rbw_g',
      'rbw_b',
      'rbw_i',
      'rbw_v',
    );

    $result = array();
    foreach (\Facebook\Libphutil\Functions\utf8::phutil_utf8v($source) as $character) {
      if ($character == ' ' || $character == "\n") {
        $result[] = $character;
        continue;
      }
      $result[] = \Facebook\Libphutil\Functions\render::phutil_tag(
        'span',
        array('class' => $colors[$color]),
        $character);
      $color = ($color + 1) % count($colors);
    }

    $result = \Facebook\Libphutil\Functions\render::phutil_implode_html('', $result);
    return new \Facebook\Libphutil\ImmediateFuture($result);
  }
}
