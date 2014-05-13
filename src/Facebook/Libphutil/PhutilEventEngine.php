<?php

namespace Facebook\Libphutil;

/**
 * @group event
 */
class PhutilEventEngine {

  private static $instance;

  private $listeners = array();

  private function __construct() {
    // <empty>
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new \Facebook\Libphutil\PhutilEventEngine();
    }
    return self::$instance;
  }

  public function addListener(\Facebook\Libphutil\PhutilEventListener $listener, $type) {
    $this->listeners[$type][] = $listener;
    return $this;
  }

  /**
   * Get all the objects currently listening to any event.
   */
  public function getAllListeners() {
    $listeners = \Facebook\Libphutil\Functions\utils::array_mergev($this->listeners);
    $listeners = \Facebook\Libphutil\Functions\utils::mpull($listeners, null, 'getListenerID');
    return $listeners;
  }

  public static function dispatchEvent(\Facebook\Libphutil\PhutilEvent $event) {
    $instance = self::getInstance();

    $listeners = \Facebook\Libphutil\Functions\utils::idx($instance->listeners, $event->getType(), array());
    $global_listeners = \Facebook\Libphutil\Functions\utils::idx(
      $instance->listeners,
      \Facebook\Libphutil\PhutilEventType::TYPE_ALL,
      array());

    // Merge and deduplicate listeners (we want to send the event to each
    // listener only once, even if it satisfies multiple criteria for the
    // event).
    $listeners = array_merge($listeners, $global_listeners);
    $listeners = \Facebook\Libphutil\Functions\utils::mpull($listeners, null, 'getListenerID');

    $profiler = \Facebook\Libphutil\PhutilServiceProfiler::getInstance();
    $profiler_id = $profiler->beginServiceCall(
      array(
        'type'  => 'event',
        'kind'  => $event->getType(),
        'count' => count($listeners),
      ));

    $caught = null;
    try {
      foreach ($listeners as $listener) {
        if ($event->isStopped()) {
          // Do this first so if someone tries to dispatch a stopped event it
          // doesn't go anywhere. Silly but less surprising.
          break;
        }
        $listener->handleEvent($event);
      }
    } catch (\Exception $ex) {
      $profiler->endServiceCall($profiler_id, array());
      throw $ex;
    }

    $profiler->endServiceCall($profiler_id, array());
  }

}
