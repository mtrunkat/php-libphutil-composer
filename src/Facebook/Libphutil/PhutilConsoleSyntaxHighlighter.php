<?php

namespace Facebook\Libphutil;

/**
 * Simple syntax highlighter for console output. We just try to highlight the
 * commands so it's easier to follow transcripts.
 *
 * @group markup
 */
class PhutilConsoleSyntaxHighlighter {

  private $config = array();
  private $replaceClass;

  public function setConfig($key, $value) {
    $this->config[$key] = $value;
    return $this;
  }

  public function getHighlightFuture($source) {

    $in_command = false;
    $lines = explode("\n", $source);
    foreach ($lines as $key => $line) {
      $matches = null;

      // Parse commands like this:
      //
      //   some/path/ $ ./bin/example # Do things
      //
      // ...into path, command, and comment components.

      $pattern =
        '@'.
        ($in_command ? '()(.*?)' : '^(\S+[\\\\/] )?([$] .*?)').
        '(#.*|\\\\)?$@';

      if (preg_match($pattern, $line, $matches)) {
        $lines[$key] = \Facebook\Libphutil\Functions\render::hsprintf(
          '%s<span class="gp">%s</span>%s',
          $matches[1],
          $matches[2],
          (!empty($matches[3])
            ? \Facebook\Libphutil\Functions\render::hsprintf('<span class="k">%s</span>', $matches[3])
            : ''));
        $in_command = (\Facebook\Libphutil\Functions\utils::idx($matches, 3) == '\\');
      } else {
        $lines[$key] = \Facebook\Libphutil\Functions\render::hsprintf('<span class="go">%s</span>', $line);
      }
    }
    $lines = \Facebook\Libphutil\Functions\render::phutil_implode_html("\n", $lines);

    return new \Facebook\Libphutil\ImmediateFuture($lines);
  }

}
