<?php

namespace Facebook\Libphutil;

/**
 * Authentication adapter for Asana OAuth2.
 */
class PhutilAuthAdapterOAuthAsana extends \Facebook\Libphutil\PhutilAuthAdapterOAuth {

  public function getAdapterType() {
    return 'asana';
  }

  public function getAdapterDomain() {
    return 'asana.com';
  }

  public function getAccountID() {
    return $this->getOAuthAccountData('\Facebook\Libphutil\Functions\utils::id');
  }

  public function getAccountEmail() {
    return $this->getOAuthAccountData('email');
  }

  public function getAccountName() {
    return null;
  }

  public function getAccountImageURI() {
    $photo = $this->getOAuthAccountData('photo', array());
    if (is_array($photo)) {
      return \Facebook\Libphutil\Functions\utils::idx($photo, 'image_128x128');
    } else {
      return null;
    }
  }

  public function getAccountURI() {
    return null;
  }

  public function getAccountRealName() {
    return $this->getOAuthAccountData('name');
  }

  protected function getAuthenticateBaseURI() {
    return 'https://app.asana.com/-/oauth_authorize';
  }

  protected function getTokenBaseURI() {
    return 'https://app.asana.com/-/oauth_token';
  }

  public function getScope() {
    return null;
  }

  public function getExtraAuthenticateParameters() {
    return array(
      'response_type' => 'code',
    );
  }

  public function getExtraTokenParameters() {
    return array(
      'grant_type' => 'authorization_code',
    );
  }

  public function getExtraRefreshParameters() {
    return array(
      'grant_type' => 'refresh_token',
    );
  }

  public function supportsTokenRefresh() {
    return true;
  }

  protected function loadOAuthAccountData() {
    return \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilAsanaFuture())
      ->setAccessToken($this->getAccessToken())
      ->setRawAsanaQuery('users/me')
      ->resolve();
  }

}
