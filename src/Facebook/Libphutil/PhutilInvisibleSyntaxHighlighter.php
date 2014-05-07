<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilInvisibleSyntaxHighlighter {

  private $config = array();

  public function setConfig($key, $value) {
    $this->config[$key] = $value;
    return $this;
  }

  public function getHighlightFuture($source) {
    $keys      = array_map("chr", range(0x0, 0x1F));
    $vals      = array_map(
      array($this, "decimalToHtmlEntityDecoded"), range(0x2400, 0x241F));

    $invisible = array_combine($keys, $vals);

    $result = array();
    foreach (str_split($source) as $character) {
      if (isset($invisible[$character])) {
        $result[] = \Facebook\Libphutil\Functions\render::phutil_tag(
          'span',
          array("class" => "invisible"),
          $invisible[$character]);

        if ($character === "\n") {
          $result[] = $character;
        }
      } else {
        $result[] = $character;
      }
    }

    $result = \Facebook\Libphutil\Functions\render::phutil_implode_html('', $result);
    return new \Facebook\Libphutil\ImmediateFuture($result);
  }

  private function decimalToHtmlEntityDecoded($dec) {
    return html_entity_decode("&#{$dec};");
  }
}
