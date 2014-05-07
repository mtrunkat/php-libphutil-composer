<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilDefaultSyntaxHighlighterEnginePygmentsFuture
  extends \Facebook\Libphutil\FutureProxy {

  private $source;
  private $scrub;

  public function __construct(\Facebook\Libphutil\Future $proxied, $source, $scrub = false) {
    parent::__construct($proxied);
    $this->source = $source;
    $this->scrub = $scrub;
  }

  protected function didReceiveResult($result) {
    list($err, $stdout, $stderr) = $result;

    if (!$err && strlen($stdout)) {
      // Strip off fluff Pygments adds.
      $stdout = preg_replace(
        '@^<div class="highlight"><pre>(.*)</pre></div>\s*$@s',
        '\1',
        $stdout);
      if ($this->scrub) {
        $stdout = preg_replace('/^.*\n/', '', $stdout);
      }
      return \Facebook\Libphutil\Functions\render::phutil_safe_html($stdout);
    }

    throw new \Facebook\Libphutil\PhutilSyntaxHighlighterException($stderr, $err);
  }

}
