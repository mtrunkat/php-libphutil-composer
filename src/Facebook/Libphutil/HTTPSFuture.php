<?php

namespace Facebook\Libphutil;

/**
 * Very basic HTTPS future.
 *
 * @group futures
 */
final class HTTPSFuture extends \Facebook\Libphutil\BaseHTTPFuture {

  private static $multi;
  private static $results = array();
  private static $pool = array();
  private static $globalCABundle;
  private static $blindTrustDomains = array();

  private $handle;
  private $profilerCallID;
  private $cabundle;
  private $followLocation = true;
  private $responseBuffer = '';
  private $responseBufferPos;
  private $files = array();
  private $temporaryFiles = array();

  /**
   * Create a temp file containing an SSL cert, and use it for this session.
   *
   * This allows us to do host-specific SSL certificates in whatever client
   * is using libphutil. e.g. in Arcanist, you could add an "ssl_cert" key
   * to a specific host in ~/.arcrc and use that.
   *
   * cURL needs this to be a file, it doesn't seem to be able to handle a string
   * which contains the cert. So we make a temporary file and store it there.
   *
   * @param string The multi-line, possibly lengthy, SSL certificate to use.
   * @return this
   */
  public function setCABundleFromString($certificate) {
    $temp = new \Facebook\Libphutil\TempFile();
    \Facebook\Libphutil\Filesystem::writeFile($temp, $certificate);
    $this->cabundle = $temp;
    return $this;
  }

  /**
   * Set the SSL certificate to use for this session, given a path.
   *
   * @param string The path to a valid SSL certificate for this session
   * @return this
   */
  public function setCABundleFromPath($path) {
    $this->cabundle = $path;
    return $this;
  }

  /**
   * Get the path to the SSL certificate for this session.
   *
   * @return string|null
   */
  public function getCABundle() {
    return $this->cabundle;
  }

  /**
   * Set whether Location headers in the response will be respected.
   * The default is true.
   *
   * @param boolean true to follow any Location header present in the response,
   *                false to return the request directly
   * @return this
   */
  public function setFollowLocation($follow) {
    $this->followLocation = $follow;
    return $this;
  }

  /**
   * Get whether Location headers in the response will be respected.
   *
   * @return boolean
   */
  public function getFollowLocation() {
    return $this->followLocation;
  }

  /**
   * Set the fallback CA certificate if one is not specified
   * for the session, given a path.
   *
   * @param string The path to a valid SSL certificate
   * @return void
   */
  public static function setGlobalCABundleFromPath($path) {
    self::$globalCABundle = $path;
  }
  /**
   * Set the fallback CA certificate if one is not specified
   * for the session, given a string.
   *
   * @param string The certificate
   * @return void
   */
  public static function setGlobalCABundleFromString($certificate) {
    $temp = new \Facebook\Libphutil\TempFile();
    \Facebook\Libphutil\Filesystem::writeFile($temp, $certificate);
    self::$globalCABundle = $temp;
  }

  /**
   * Get the fallback global CA certificate
   *
   * @return string
   */
  public static function getGlobalCABundle() {
    return self::$globalCABundle;
  }

  /**
   * Set a list of domains to blindly trust. Certificates for these domains
   * will not be validated.
   *
   * @param list<string> List of domain names to trust blindly.
   * @return void
   */
  public static function setBlindlyTrustDomains(array $domains) {
    self::$blindTrustDomains = \Facebook\Libphutil\Functions\utils::array_fuse($domains);
  }

  /**
   * Load contents of remote URI. Behaves pretty much like
   *  `@file_get_contents($uri)` but doesn't require `allow_url_fopen`.
   *
   * @param string
   * @param float
   * @return string|false
   */
  public static function loadContent($uri, $timeout = null) {
    $future = new \Facebook\Libphutil\HTTPSFuture($uri);
    if ($timeout !== null) {
      $future->setTimeout($timeout);
    }
    try {
      list($body) = $future->resolvex();
      return $body;
    } catch (\Facebook\Libphutil\HTTPFutureResponseStatus $ex) {
      return false;
    }
  }

  /**
   * Attach a file to the request.
   *
   * @param string  HTTP parameter name.
   * @param string  File content.
   * @param string  File name.
   * @param string  File mime type.
   * @return this
   */
  public function attachFileData($key, $data, $name, $mime_type) {
    if (isset($this->files[$key])) {
      throw new \Exception(
        \Facebook\Libphutil\Functions\pht::pht(
          '\Facebook\Libphutil\HTTPSFuture currently supports only one file attachment for each '.
          'parameter name. You are trying to attach two different files with '.
          'the same parameter, "%s".',
          $key));
    }

    $this->files[$key] = array(
      'data' => $data,
      'name' => $name,
      'mime' => $mime_type,
    );

    return $this;
  }

  public function isReady() {
    if (isset($this->result)) {
      return true;
    }

    $uri = $this->getURI();
    $domain = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilURI($uri))->getDomain();

    if (!$this->handle) {
      $profiler = \Facebook\Libphutil\PhutilServiceProfiler::getInstance();
      $this->profilerCallID = $profiler->beginServiceCall(
        array(
          'type' => 'http',
          'uri' => $uri,
        ));

      if (!self::$multi) {
        self::$multi = curl_multi_init();
        if (!self::$multi) {
          throw new \Exception('curl_multi_init() failed!');
        }
      }

      if (!empty(self::$pool[$domain])) {
        $curl = array_pop(self::$pool[$domain]);
      } else {
        $curl = curl_init();
        if (!$curl) {
          throw new \Exception('curl_init() failed!');
        }
      }

      $this->handle = $curl;
      curl_multi_add_handle(self::$multi, $curl);

      curl_setopt($curl, CURLOPT_URL, $uri);

      if (defined('CURLOPT_PROTOCOLS')) {
        // cURL supports a lot of protocols, and by default it will honor
        // redirects across protocols (for instance, from HTTP to POP3). Beyond
        // being very silly, this also has security implications:
        //
        //   http://blog.volema.com/curl-rce.html
        //
        // Disable all protocols other than HTTP and HTTPS.

        $allowed_protocols = CURLPROTO_HTTPS | CURLPROTO_HTTP;
        curl_setopt($curl, CURLOPT_PROTOCOLS, $allowed_protocols);
        curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, $allowed_protocols);
      }

      $data = $this->formatRequestDataForCURL();
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

      $headers = $this->getHeaders();

      $saw_expect = false;
      for ($ii = 0; $ii < count($headers); $ii++) {
        list($name, $value) = $headers[$ii];
        $headers[$ii] = $name.': '.$value;
        if (!strncasecmp($name, 'Expect', strlen('Expect'))) {
          $saw_expect = true;
        }
      }
      if (!$saw_expect) {
        // cURL sends an "Expect" header by default for certain requests. While
        // there is some reasoning behind this, it causes a practical problem
        // in that lighttpd servers reject these requests with a 417. Both sides
        // are locked in an eternal struggle (lighttpd has introduced a
        // 'server.reject-expect-100-with-417' option to deal with this case).
        //
        // The ostensibly correct way to suppress this behavior on the cURL side
        // is to add an empty "Expect:" header. If we haven't seen some other
        // explicit "Expect:" header, do so.
        //
        // See here, for example, although this issue is fairly widespread:
        //   http://curl.haxx.se/mail/archive-2009-07/0008.html
        $headers[] = 'Expect:';
      }
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

      // Set the requested HTTP method, e.g. GET / POST / PUT.
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->getMethod());

      // Make sure we get the headers and data back.
      curl_setopt($curl, CURLOPT_HEADER, true);
      curl_setopt($curl, CURLOPT_WRITEFUNCTION,
        array($this, 'didReceiveDataCallback'));

      if ($this->followLocation) {
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 20);
      }

      if (defined('CURLOPT_TIMEOUT_MS')) {
        // If CURLOPT_TIMEOUT_MS is available, use the higher-precision timeout.
        $timeout = max(1, ceil(1000 * $this->getTimeout()));
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout);
      } else {
        // Otherwise, fall back to the lower-precision timeout.
        $timeout = max(1, ceil($this->getTimeout()));
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
      }

      // Try some decent fallbacks here:
      // - First, check if a bundle is set explicit for this request, via
      //   `setCABundle()` or similar.
      // - Then, check if a global bundle is set explicitly for all requests,
      //   via `setGlobalCABundle()` or similar.
      // - Then, if a local custom.pem exists, use that, because it probably
      //   means that the user wants to override everything (also because the
      //   user might not have access to change the box's php.ini to add
      //   curl.cainfo).
      // - Otherwise, try using curl.cainfo. If it's set explicitly, it's
      //   probably reasonable to try using it before we fall back to what
      //   libphutil ships with.
      // - Lastly, try the default that libphutil ships with. If it doesn't
      //   work, give up and yell at the user.

      if (!$this->getCABundle()) {
        $caroot = dirname(\Facebook\Libphutil\Functions\moduleutils::phutil_get_library_root('phutil')).'/resources/ssl/';
        $ini_val = ini_get('curl.cainfo');
        if (self::getGlobalCABundle()) {
          $this->setCABundleFromPath(self::getGlobalCABundle());
        } else if (\Facebook\Libphutil\Filesystem::pathExists($caroot.'custom.pem')) {
          $this->setCABundleFromPath($caroot.'custom.pem');
        } else if ($ini_val) {
          // TODO: We can probably do a pathExists() here, even.
          $this->setCABundleFromPath($ini_val);
        } else {
          $this->setCABundleFromPath($caroot.'default.pem');
        }
      }

      curl_setopt($curl, CURLOPT_CAINFO, $this->getCABundle());

      $domain = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilURI($uri))->getDomain();
      if (!empty(self::$blindTrustDomains[$domain])) {
        // Disable peer verification for domains that we blindly trust.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      } else {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
      }

      curl_setopt($curl, CURLOPT_SSLVERSION, 0);
    } else {
      $curl = $this->handle;

      if (!self::$results) {
        // NOTE: In curl_multi_select(), PHP calls curl_multi_fdset() but does
        // not check the return value of &maxfd for -1 until recent versions
        // of PHP (5.4.8 and newer). cURL may return -1 as maxfd in some unusual
        // situations; if it does, PHP enters select() with nfds=0, which blocks
        // until the timeout is reached.
        //
        // We could try to guess whether this will happen or not by examining
        // the version identifier, but we can also just sleep for only a short
        // period of time.
        curl_multi_select(self::$multi, 0.01);
      }
    }

    do {
      $active = null;
      $result = curl_multi_exec(self::$multi, $active);
    } while ($result == CURLM_CALL_MULTI_PERFORM);

    while ($info = curl_multi_info_read(self::$multi)) {
      if ($info['msg'] == CURLMSG_DONE) {
        self::$results[(int)$info['handle']] = $info;
      }
    }

    if (!array_key_exists((int)$curl, self::$results)) {
      return false;
    }

    // The request is complete, so release any temporary files we wrote
    // earlier.
    $this->temporaryFiles = array();

    $info = self::$results[(int)$curl];
    $result = $this->responseBuffer;
    $err_code = $info['result'];

    if ($err_code) {
      $status = new \Facebook\Libphutil\HTTPFutureResponseStatusCURL($err_code, $uri);
      $body = null;
      $headers = array();
      $this->result = array($status, $body, $headers);
    } else {
      // cURL returns headers of all redirects, we strip all but the final one.
      $redirects = curl_getinfo($curl, CURLINFO_REDIRECT_COUNT);
      $result = preg_replace('/^(.*\r\n\r\n){'.$redirects.'}/sU', '', $result);
      $this->result = $this->parseRawHTTPResponse($result);
    }

    curl_multi_remove_handle(self::$multi, $curl);
    unset(self::$results[(int)$curl]);

    // NOTE: We want to use keepalive if possible. Return the handle to a
    // pool for the domain; don't close it.
    self::$pool[$domain][] = $curl;

    $profiler = \Facebook\Libphutil\PhutilServiceProfiler::getInstance();
    $profiler->endServiceCall($this->profilerCallID, array());

    return true;
  }


  /**
   * Callback invoked by cURL as it reads HTTP data from the response. We save
   * the data to a buffer.
   */
  public function didReceiveDataCallback($handle, $data) {
    $this->responseBuffer .= $data;
    return strlen($data);
  }


  /**
   * Read data from the response buffer.
   *
   * NOTE: Like @{class:ExecFuture}, this method advances a read cursor but
   * does not discard the data. The data will still be buffered, and it will
   * all be returned when the future resolves. To discard the data after
   * reading it, call @{method:discardBuffers}.
   *
   * @return string Response data, if available.
   */
  public function read() {
    $result = substr($this->responseBuffer, $this->responseBufferPos);
    $this->responseBufferPos = strlen($this->responseBuffer);
    return $result;
  }


  /**
   * Discard any buffered data. Normally, you call this after reading the
   * data with @{method:read}.
   *
   * @return this
   */
  public function discardBuffers() {
    $this->responseBuffer = '';
    $this->responseBufferPos = 0;
    return $this;
  }


  /**
   * Produces a value safe to pass to `CURLOPT_POSTFIELDS`.
   *
   * @return wild   Some value, suitable for use in `CURLOPT_POSTFIELDS`.
   */
  private function formatRequestDataForCURL() {
    // We're generating a value to hand to cURL as CURLOPT_POSTFIELDS. The way
    // cURL handles this value has some tricky caveats.

    // First, we can return either an array or a query string. If we return
    // an array, we get a "multipart/form-data" request. If we return a
    // query string, we get an "application/x-www-form-urlencoded" request.

    // Second, if we return an array we can't duplicate keys. The user might
    // want to send the same parameter multiple times.

    // Third, if we return an array and any of the values start with "@",
    // cURL includes arbitrary files off disk and sends them to an untrusted
    // remote server. For example, an array like:
    //
    //   array('name' => '@/usr/local/secret')
    //
    // ...will attempt to read that file off disk and transmit its contents with
    // the request. This behavior is pretty surprising, and it can easily
    // become a relatively severe security vulnerability which allows an
    // attacker to read any file the HTTP process has access to. Since this
    // feature is very dangerous and not particularly useful, we prevent its
    // use. Broadly, this means we must reject some requests because they
    // contain an "@" in an inconvenient place.

    // Generally, to avoid the "@" case and because most servers usually
    // expect "application/x-www-form-urlencoded" data, we try to return a
    // string unless there are files attached to this request.

    $data = $this->getData();
    $files = $this->files;

    $any_data = ($data || (is_string($data) && strlen($data)));
    $any_files = (bool)$this->files;

    if (!$any_data && !$any_files) {
      // No files or data, so just bail.
      return null;
    }

    if (!$any_files) {
      // If we don't have any files, just encode the data as a query string,
      // make sure it's not including any files, and we're good to go.
      if (is_array($data)) {
        $data = http_build_query($data, '', '&');
      }

      $this->checkForDangerousCURLMagic($data, $is_query_string = true);

      return $data;
    }

    // If we've made it this far, we have some files, so we need to return
    // an array. First, convert the other data into an array if it isn't one
    // already.

    if (is_string($data)) {
      // NOTE: We explicitly don't want fancy array parsing here, so just
      // do a basic parse and then convert it into a dictionary ourselves.
      $parser = new \Facebook\Libphutil\PhutilQueryStringParser();
      $pairs = $parser->parseQueryStringToPairList($data);

      $map = array();
      foreach ($pairs as $pair) {
        list($key, $value) = $pair;
        if (array_key_exists($key, $map)) {
          throw new \Exception(
            \Facebook\Libphutil\Functions\pht::pht(
              'Request specifies two values for key "%s", but parameter '.
              'names must be unique if you are posting file data due to '.
              'limitations with cURL.'));
        }
        $map[$key] = $value;
      }

      $data = $map;
    }

    foreach ($data as $key => $value) {
      $this->checkForDangerousCURLMagic($value, $is_query_string = false);
    }

    foreach ($this->files as $name => $info) {
      if (array_key_exists($name, $data)) {
        throw new \Exception(
          \Facebook\Libphutil\Functions\pht::pht(
            'Request specifies a file with key "%s", but that key is '.
            'also defined by normal request data. Due to limitations '.
            'with cURL, requests that post file data must use unique '.
            'keys.'));
      }

      $tmp = new \Facebook\Libphutil\TempFile($info['name']);
      \Facebook\Libphutil\Filesystem::writeFile($tmp, $info['data']);
      $this->temporaryFiles[] = $tmp;

      // In 5.5.0 and later, we can use CURLFile. Prior to that, we have to
      // use this "@" stuff.

      if (class_exists('CURLFile')) {
        $file_value = new CURLFile((string)$tmp, $info['mime'], $info['name']);
      } else {
        $file_value = '@'.(string)$tmp;
      }

      $data[$name] = $file_value;
    }

    return $data;
  }


  /**
   * Detect strings which will cause cURL to do horrible, insecure things.
   *
   * @param string  Possibly dangerous string.
   * @param bool    True if this string is being used as part of a query string.
   * @return void
   */
  private function checkForDangerousCURLMagic($string, $is_query_string) {
    if (empty($string[0]) || ($string[0] != '@')) {
      // This isn't an "@..." string, so it's fine.
      return;
    }

    if ($is_query_string) {
      if (version_compare(phpversion(), '5.2.0', '<')) {
        throw new \Exception(
          \Facebook\Libphutil\Functions\pht::pht(
            'Attempting to make an HTTP request, but query string data begins '.
            'with "@". Prior to PHP 5.2.0 this reads files off disk, which '.
            'creates a wide attack window for security vulnerabilities. '.
            'Upgrade PHP or avoid making cURL requests which begin with "@".'));
      }

      // This is safe if we're on PHP 5.2.0 or newer.
      return;
    }

    throw new \Exception(
      \Facebook\Libphutil\Functions\pht::pht(
        'Attempting to make an HTTP request which includes file data, but '.
        'the value of a query parameter begins with "@". PHP interprets '.
        'these values to mean that it should read arbitrary files off disk '.
        'and transmit them to remote servers. Declining to make this '.
        'request.'));
  }

}
