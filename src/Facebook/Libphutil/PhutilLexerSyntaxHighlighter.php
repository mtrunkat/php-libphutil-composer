<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilLexerSyntaxHighlighter extends \Facebook\Libphutil\PhutilSyntaxHighlighter {

  private $config = array();

  public function setConfig($key, $value) {
    $this->config[$key] = $value;
    return $this;
  }

  public function getHighlightFuture($source) {
    $strip = false;
    $state = 'start';
    $lang = \Facebook\Libphutil\Functions\utils::idx($this->config, 'language');

    if ($lang == 'php') {
      if (strpos($source, '<?') === false) {
        $state = 'php';
      }
    }

    $lexer = \Facebook\Libphutil\Functions\utils::idx($this->config, 'lexer');
    $tokens = $lexer->getTokens($source, $state);
    $tokens = $lexer->mergeTokens($tokens);

    $result = array();
    foreach ($tokens as $token) {
      list($type, $value, $context) = $token;

      $data_name = null;
      switch ($type) {
        case 'nc':
        case 'nf':
        case 'na':
          $data_name = $value;
          break;
      }

      if (strpos($value, "\n") !== false) {
        $value = explode("\n", $value);
      } else {
        $value = array($value);
      }
      foreach ($value as $part) {
        if (strlen($part)) {
          if ($type) {
            $result[] = \Facebook\Libphutil\Functions\render::phutil_tag(
              'span',
              array(
                'class' => $type,
                'data-symbol-context' => $context,
                'data-symbol-name' => $data_name,
              ),
              $part);
          } else {
            $result[] = $part;
          }
        }
        $result[] = "\n";
      }

      // Throw away the last "\n".
      array_pop($result);
    }

    $result = \Facebook\Libphutil\Functions\render::phutil_implode_html('', $result);

    return new \Facebook\Libphutil\ImmediateFuture($result);
  }

}
