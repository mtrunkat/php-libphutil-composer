<?php

namespace Facebook\Libphutil;

/**
 * @group event
 * @stable
 */
abstract class PhutilEventListener {

  private $listenerID;
  private static $nextListenerID = 1;

  final public function __construct() {
    // <empty>
  }

  abstract public function register();
  abstract public function handleEvent(\Facebook\Libphutil\PhutilEvent $event);

  final public function listen($type) {
    $engine = \Facebook\Libphutil\PhutilEventEngine::getInstance();
    $engine->addListener($this, $type);
  }


  /**
   * Return a scalar ID unique to this listener. This is used to deduplicate
   * listeners which match events on multiple rules, so they are invoked only
   * once.
   *
   * @return int A scalar unique to this object instance.
   */
  final public function getListenerID() {
    if (!$this->listenerID) {
      $this->listenerID = self::$nextListenerID;
      self::$nextListenerID++;
    }
    return $this->listenerID;
  }


}
