<?php

namespace Facebook\Libphutil;

/**
 * @group console
 */
final class PhutilConsoleServerChannel extends \Facebook\Libphutil\PhutilChannelChannel {

  public function didReceiveStderr(\Facebook\Libphutil\PhutilExecChannel $channel, $stderr) {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR)
      ->setData(array('%s', $stderr));
    $this->getUnderlyingChannel()->addMessage($message);
  }

}
