<?php

namespace Facebook\Libphutil;

/**
 * Authentication adapter for Mozilla's Persona.
 */
final class PhutilAuthAdapterPersona extends \Facebook\Libphutil\PhutilAuthAdapter {

  private $audience;
  private $assertion;
  private $accountData;

  public function setAssertion($assertion) {
    $this->assertion = $assertion;
    return $this;
  }

  public function setAudience($audience) {
    $this->audience = $audience;
    return $this;
  }

  public function getAdapterDomain() {
    return 'verifier.login.persona.org';
  }

  public function getAdapterType() {
    return 'persona';
  }

  public function getAccountEmail() {
    return $this->getAccountID();
  }

  public function getAccountID() {
    if ($this->accountData === null) {
      $verify_uri = 'https://verifier.login.persona.org/verify';
      $data = array(
        'audience' => $this->audience,
        'assertion' => $this->assertion,
      );

      list($body) = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\HTTPSFuture($verify_uri, json_encode($data)))
        ->setMethod('POST')
        ->addHeader('Content-Type', 'application/json')
        ->resolvex();

      $response = json_decode($body, true);
      if (!is_array($response)) {
        throw new \Exception("Unexpected Persona response: {$body}");
      }

      $audience = \Facebook\Libphutil\Functions\utils::idx($response, 'audience');
      if ($audience != $this->audience) {
        throw new \Exception("Mismatched Persona audience: {$audience}");
      }

      if (\Facebook\Libphutil\Functions\utils::idx($response, 'status') !== 'okay') {
        $reason = \Facebook\Libphutil\Functions\utils::idx($response, 'reason', 'Unknown');
        throw new \Exception("Persona login failed: {$reason}");
      }

      $this->accountData = $response;
    }

    return \Facebook\Libphutil\Functions\utils::idx($this->accountData, 'email');
  }

}
