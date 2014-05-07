<?php

namespace Facebook\Libphutil;

/**
 * Authentication adapter for Facebook OAuth2.
 */
final class PhutilAuthAdapterOAuthFacebook extends \Facebook\Libphutil\PhutilAuthAdapterOAuth {

  private $requireSecureBrowsing;

  public function setRequireSecureBrowsing($require_secure_browsing) {
    $this->requireSecureBrowsing = $require_secure_browsing;
    return $this;
  }

  public function getAdapterType() {
    return 'facebook';
  }

  public function getAdapterDomain() {
    return 'facebook.com';
  }

  public function getAccountID() {
    return $this->getOAuthAccountData('\Facebook\Libphutil\Functions\utils::id');
  }

  public function getAccountEmail() {
    return $this->getOAuthAccountData('email');
  }

  public function getAccountName() {
    $link = $this->getOAuthAccountData('link');
    if (!$link) {
      return null;
    }

    $matches = null;
    if (!preg_match('@/([^/]+)$@', $link, $matches)) {
      return null;
    }

    return $matches[1];
  }

  public function getAccountImageURI() {
    $picture = $this->getOAuthAccountData('picture');
    if ($picture) {
      $picture_data = \Facebook\Libphutil\Functions\utils::idx($picture, 'data');
      if ($picture_data) {
        return \Facebook\Libphutil\Functions\utils::idx($picture_data, 'url');
      }
    }
    return null;
  }

  public function getAccountURI() {
    return $this->getOAuthAccountData('link');
  }

  public function getAccountRealName() {
    return $this->getOAuthAccountData('name');
  }

  public function getAccountSecuritySettings() {
    return $this->getOAuthAccountData('security_settings');
  }

  protected function getAuthenticateBaseURI() {
    return 'https://www.facebook.com/dialog/oauth';
  }

  protected function getTokenBaseURI() {
    return 'https://graph.facebook.com/oauth/access_token';
  }

  protected function loadOAuthAccountData() {
    $fields = array(
      '\Facebook\Libphutil\Functions\utils::id',
      'name',
      'email',
      'link',
      'security_settings',
      'picture',
    );

    $uri = new \Facebook\Libphutil\PhutilURI('https://graph.facebook.com/me');
    $uri->setQueryParam('access_token', $this->getAccessToken());
    $uri->setQueryParam('fields', implode(',', $fields));
    list($body) = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\HTTPSFuture($uri))->resolvex();

    $data = json_decode($body, true);
    if (!is_array($data)) {
      throw new \Exception(
        "Expected valid JSON response from Facebook account data request, ".
        "got: ".$body);
    }

    if ($this->requireSecureBrowsing) {
      if (empty($data['security_settings']['secure_browsing']['enabled'])) {
        throw new \Exception(
          \Facebook\Libphutil\Functions\pht::pht(
            "This Phabricator install requires you to enable Secure Browsing ".
            "on your Facebook account in order to use it to log in to ".
            "Phabricator. For more information, see %s",
            'https://www.facebook.com/help/156201551113407/'));
      }
    }

    return $data;
  }

}
