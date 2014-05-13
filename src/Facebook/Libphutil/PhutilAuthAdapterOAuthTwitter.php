<?php

namespace Facebook\Libphutil;

/**
 * Authentication adapter for Twitter OAuth1.
 */
class PhutilAuthAdapterOAuthTwitter extends \Facebook\Libphutil\PhutilAuthAdapterOAuth1 {

  private $userInfo;

  public function getAccountID() {
    return \Facebook\Libphutil\Functions\utils::idx($this->getHandshakeData(), 'user_id');
  }

  public function getAccountName() {
    return \Facebook\Libphutil\Functions\utils::idx($this->getHandshakeData(), 'screen_name');
  }

  public function getAccountURI() {
    $name = $this->getAccountName();
    if (strlen($name)) {
      return 'https://twitter.com/'.$name;
    }
    return null;
  }

  public function getAccountImageURI() {
    $info = $this->getUserInfo();
    return \Facebook\Libphutil\Functions\utils::idx($info, 'profile_image_url');
  }

  public function getAccountRealName() {
    $info = $this->getUserInfo();
    return \Facebook\Libphutil\Functions\utils::idx($info, 'name');
  }

  public function getAdapterType() {
    return 'twitter';
  }

  public function getAdapterDomain() {
    return 'twitter.com';
  }

  protected function getRequestTokenURI() {
    return 'https://api.twitter.com/oauth/request_token';
  }

  protected function getAuthorizeTokenURI() {
    return 'https://api.twitter.com/oauth/authorize';
  }

  protected function getValidateTokenURI() {
    return 'https://api.twitter.com/oauth/access_token';
  }

  private function getUserInfo() {
    if ($this->userInfo === null) {
      $uri = new \Facebook\Libphutil\PhutilURI('https://api.twitter.com/1.1/users/show.json');
      $uri->setQueryParams(
        array(
          'user_id' => $this->getAccountID(),
        ));

      $data = $this->newOAuth1Future($uri)
        ->setMethod('GET')
        ->resolveJSON();

      $this->userInfo = $data;
    }
    return $this->userInfo;
  }


}
