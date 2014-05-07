<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
abstract class PhutilSyntaxHighlighterEngine {
  abstract public function setConfig($key, $value);
  abstract public function getHighlightFuture($language, $source);
  abstract public function getLanguageFromFilename($filename);

  final public function highlightSource($language, $source) {
    try {
      return $this->getHighlightFuture($language, $source)->resolve();
    } catch (\Facebook\Libphutil\PhutilSyntaxHighlighterException $ex) {
      return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilDefaultSyntaxHighlighter())
        ->getHighlightFuture($source)
        ->resolve();
    }
  }

}
