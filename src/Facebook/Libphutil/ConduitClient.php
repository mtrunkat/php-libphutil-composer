<?php

namespace Facebook\Libphutil;

/**
 * @group conduit
 */
final class ConduitClient {

  private $uri;
  private $connectionID;
  private $sessionKey;
  private $timeout = 300.0;
  private $username;
  private $password;

  public function getConnectionID() {
    return $this->connectionID;
  }

  public function __construct($uri) {
    $this->uri = new \Facebook\Libphutil\PhutilURI($uri);
    if (!strlen($this->uri->getDomain())) {
      throw new \Exception("Conduit URI '{$uri}' must include a valid host.");
    }
  }

  public function callMethodSynchronous($method, array $params) {
    return $this->callMethod($method, $params)->resolve();
  }

  public function didReceiveResponse($method, $data) {
    if ($method == 'conduit.connect') {
      $this->sessionKey = \Facebook\Libphutil\Functions\utils::idx($data, 'sessionKey');
      $this->connectionID = \Facebook\Libphutil\Functions\utils::idx($data, 'connectionID');
    }
    return $data;
  }

  public function setTimeout($timeout) {
    $this->timeout = $timeout;
    return $this;
  }

  public function callMethod($method, array $params) {

    $meta = array();

    if ($this->sessionKey) {
      $meta['sessionKey'] = $this->sessionKey;
    }

    if ($this->connectionID) {
      $meta['connectionID'] = $this->connectionID;
    }

    if ($method == 'conduit.connect') {
      $certificate = \Facebook\Libphutil\Functions\utils::idx($params, 'certificate');
      if ($certificate) {
        $token = time();
        $params['authToken'] = $token;
        $params['authSignature'] = sha1($token.$certificate);
      }
      unset($params['certificate']);
    }

    if ($meta) {
      $params['__conduit__'] = $meta;
    }

    $uri = \Facebook\Libphutil\Functions\utils::id(clone $this->uri)->setPath('/api/'.$method);

    $data = array(
      'params'      => json_encode($params),
      'output'      => 'json',

      // This is a hint to Phabricator that the client expects a Conduit
      // response. It is not necessary, but provides better error messages in
      // some cases.
      '__conduit__' => true,
    );

    // Always use the cURL-based \Facebook\Libphutil\HTTPSFuture, for proxy support and other
    // protocol edge cases that \Facebook\Libphutil\HTTPFuture does not support.
    $core_future = new \Facebook\Libphutil\HTTPSFuture($uri, $data);

    $core_future->setMethod('POST');
    $core_future->setTimeout($this->timeout);

    if ($this->username !== null) {
      $core_future->setHTTPBasicAuthCredentials(
        $this->username,
        $this->password);
    }

    $conduit_future = new \Facebook\Libphutil\ConduitFuture($core_future);
    $conduit_future->setClient($this, $method);
    $conduit_future->beginProfile($data);
    $conduit_future->isReady();

    return $conduit_future;
  }

  public function setBasicAuthCredentials($username, $password) {
    $this->username = $username;
    $this->password = new \Facebook\Libphutil\PhutilOpaqueEnvelope($password);
    return $this;
  }

}
