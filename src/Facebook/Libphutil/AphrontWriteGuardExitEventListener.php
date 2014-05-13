<?php

namespace Facebook\Libphutil;
/**
 * Listener for "will exit abruptly" events. Shuts down the attached write guard
 * before request exits.
 *
 * @group aphront
 */
class AphrontWriteGuardExitEventListener extends \Facebook\Libphutil\PhutilEventListener {

  public function register() {
    $this->listen(\Facebook\Libphutil\PhutilEventType::TYPE_WILLEXITABRUPTLY);

    return $this;
  }

  public function handleEvent(\Facebook\Libphutil\PhutilEvent $event) {
    if (\Facebook\Libphutil\AphrontWriteGuard::isGuardActive()) {
      \Facebook\Libphutil\AphrontWriteGuard::getInstance()->disposeAbruptly();
    }
  }
}
