<?php

namespace Facebook\Libphutil;

/**
 * @group console
 */
final class PhutilConsoleServer {

  private $clients = array();
  private $handler;
  private $enableLog;

  public function handleMessage(\Facebook\Libphutil\PhutilConsoleMessage $message) {
    $data = $message->getData();
    $type = $message->getType();

    switch ($type) {

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_CONFIRM:
        $ok = \Facebook\Libphutil\Functions\format::phutil_console_confirm($data['prompt'], !$data['default']);
        return $this->buildMessage(
          \Facebook\Libphutil\PhutilConsoleMessage::TYPE_INPUT,
          $ok);

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_PROMPT:
        $response = \Facebook\Libphutil\Functions\format::phutil_console_prompt(
          $data['prompt'],
          \Facebook\Libphutil\Functions\utils::idx($data, 'history'));
        return $this->buildMessage(
          \Facebook\Libphutil\PhutilConsoleMessage::TYPE_INPUT,
          $response);

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_OUT:
        $this->writeText(STDOUT, $data);
        return null;

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR:
        $this->writeText(STDERR, $data);
        return null;

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_LOG:
        if ($this->enableLog) {
          $this->writeText(STDERR, $data);
        }
        return null;

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_ENABLED:
        switch ($data['which']) {
          case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_LOG:
            $enabled = $this->enableLog;
            break;
          default:
            $enabled = true;
            break;
        }
        return $this->buildMessage(
          \Facebook\Libphutil\PhutilConsoleMessage::TYPE_IS_ENABLED,
          $enabled);

      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_TTY:
      case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_COLS:
        switch ($data['which']) {
          case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_OUT:
            $which = STDOUT;
            break;
          case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR:
            $which = STDERR;
            break;
        }
        switch ($type) {
          case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_TTY:
            if (function_exists('posix_isatty')) {
              $is_a_tty = posix_isatty($which);
            } else {
              $is_a_tty = null;
            }
            return $this->buildMessage(
              \Facebook\Libphutil\PhutilConsoleMessage::TYPE_IS_TTY,
              $is_a_tty);
          case \Facebook\Libphutil\PhutilConsoleMessage::TYPE_COLS:
            // TODO: This is an approximation which might not be perfectly
            // accurate.
            $width = \Facebook\Libphutil\Functions\format::phutil_console_get_terminal_width();
            return $this->buildMessage(
              \Facebook\Libphutil\PhutilConsoleMessage::TYPE_COL_WIDTH,
              $width);
        }
        break;

      default:
        if ($this->handler) {
          return call_user_func($this->handler, $message);
        } else {
          throw new \Exception(
            "Received unknown console message of type '{$type}'.");
        }

    }
  }

  /**
   * Set handler called for unknown messages.
   *
   * @param callable Signature: (\Facebook\Libphutil\PhutilConsoleMessage $message).
   */
  public function setHandler($callback) {
    $this->handler = $callback;
    return $this;
  }

  private function buildMessage($type, $data) {
    $response = new \Facebook\Libphutil\PhutilConsoleMessage();
    $response->setType($type);
    $response->setData($data);
    return $response;
  }

  public function addExecFutureClient(\Facebook\Libphutil\ExecFuture $future) {
    $io_channel = new \Facebook\Libphutil\PhutilExecChannel($future);
    $protocol_channel = new \Facebook\Libphutil\PhutilPHPObjectProtocolChannel($io_channel);
    $server_channel = new \Facebook\Libphutil\PhutilConsoleServerChannel($protocol_channel);
    $io_channel->setStderrHandler(array($server_channel, 'didReceiveStderr'));
    return $this->addClient($server_channel);
  }

  public function addClient(\Facebook\Libphutil\PhutilConsoleServerChannel $channel) {
    $this->clients[] = $channel;
    return $this;
  }

  public function setEnableLog($enable) {
    $this->enableLog = $enable;
    return $this;
  }

  public function run() {
    while ($this->clients) {
      \Facebook\Libphutil\PhutilChannel::waitForAny($this->clients);
      foreach ($this->clients as $key => $client) {
        if (!$client->update()) {
          // If the client has exited, remove it from the list of clients.
          // We still need to process any remaining buffered I/O.
          unset($this->clients[$key]);
        }
        while ($message = $client->read()) {
          $response = $this->handleMessage($message);
          if ($response) {
            $client->write($response);
          }
        }
      }
    }
  }

  private function writeText($where, array $argv) {
    $text = call_user_func_array('\Facebook\Libphutil\Functions\format::phutil_console_format', $argv);
    fprintf($where, '%s', $text);
  }

}
