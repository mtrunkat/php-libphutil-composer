<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilDefaultSyntaxHighlighterEngine
  extends \Facebook\Libphutil\PhutilSyntaxHighlighterEngine {

  private $config = array();

  public function setConfig($key, $value) {
    $this->config[$key] = $value;
    return $this;
  }

  public function getLanguageFromFilename($filename) {

    static $default_map = array(
      // All files which have file extensions that we haven't already matched
      // map to their extensions.
      '@\\.([^./]+)$@' => 1,
    );

    $maps = array();
    if (!empty($this->config['filename.map'])) {
      $maps[] = $this->config['filename.map'];
    }
    $maps[] = $default_map;

    foreach ($maps as $map) {
      foreach ($map as $regexp => $lang) {
        $matches = null;
        if (preg_match($regexp, $filename, $matches)) {
          if (is_numeric($lang)) {
            return \Facebook\Libphutil\Functions\utils::idx($matches, $lang);
          } else {
            return $lang;
          }
        }
      }
    }

    return null;
  }

  public function getHighlightFuture($language, $source) {

    if ($language === null) {
      $language = \Facebook\Libphutil\PhutilLanguageGuesser::guessLanguage($source);
    }

    $have_pygments = !empty($this->config['pygments.enabled']);

    if ($language == 'php' && \Facebook\Libphutil\Functions\xhpast_parse::xhpast_is_available()) {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilXHPASTSyntaxHighlighter())
        ->getHighlightFuture($source);
    }

    if ($language == 'console') {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleSyntaxHighlighter())
        ->getHighlightFuture($source);
    }

    if ($language == 'diviner' || $language == 'remarkup') {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilDivinerSyntaxHighlighter())
        ->getHighlightFuture($source);
    }

    if ($language == 'rainbow') {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilRainbowSyntaxHighlighter())
        ->getHighlightFuture($source);
    }

    if ($language == 'php') {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilLexerSyntaxHighlighter())
        ->setConfig('lexer', new \Facebook\Libphutil\PhutilPHPFragmentLexer())
        ->setConfig('language', 'php')
        ->getHighlightFuture($source);
    }

    if ($language == 'invisible') {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilInvisibleSyntaxHighlighter())
             ->getHighlightFuture($source);
    }

    if ($have_pygments) {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilPygmentsSyntaxHighlighter())
        ->setConfig('language', $language)
        ->getHighlightFuture($source);
    }

    return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilDefaultSyntaxHighlighter())
      ->getHighlightFuture($source);
  }
}
