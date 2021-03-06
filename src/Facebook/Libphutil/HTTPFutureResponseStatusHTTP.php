<?php

namespace Facebook\Libphutil;

/**
 * @group futures
 */
class HTTPFutureResponseStatusHTTP extends \Facebook\Libphutil\HTTPFutureResponseStatus {

  private $excerpt;
  private $expect;

  public function __construct(
    $status_code,
    $body,
    array $headers,
    $expect = null) {
    // NOTE: Avoiding \Facebook\Libphutil\Functions\utf8::phutil_utf8_shorten() here because this isn't lazy
    // and responses may be large.
    if (strlen($body) > 512) {
      $excerpt = substr($body, 0, 512).'...';
    } else {
      $excerpt = $body;
    }

    $content_type = \Facebook\Libphutil\BaseHTTPFuture::getHeader($headers, 'Content-Type');
    $match = null;
    if (preg_match('/;\s*charset=([^;]+)/', $content_type, $match)) {
      $encoding = trim($match[1], "\"'");
      try {
        $excerpt = \Facebook\Libphutil\Functions\utf8::phutil_utf8_convert($excerpt, 'UTF-8', $encoding);
      } catch (\Exception $ex) {
      }
    }

    $this->excerpt = \Facebook\Libphutil\Functions\utf8::phutil_utf8ize($excerpt);
    $this->expect = $expect;

    parent::__construct($status_code);
  }

  protected function getErrorCodeType($code) {
    return 'HTTP';
  }

  public function isError() {
    if ($this->expect === null) {
        return ($this->getStatusCode() < 200) || ($this->getStatusCode() > 299);
    }

    return !in_array($this->getStatusCode(), $this->expect, true);
  }

  public function isTimeout() {
    return false;
  }

  protected function getErrorCodeDescription($code) {
    static $map = array(
      404 => 'Not Found',
      500 => 'Internal Server Error',
    );

    return \Facebook\Libphutil\Functions\utils::idx($map, $code)."\n".$this->excerpt."\n";
  }

}
