<?php

namespace Facebook\Libphutil;

/**
 * Provides access to the command-line console. Instead of reading from or
 * writing to stdin/stdout/stderr directly, this class provides a richer API
 * including support for ANSI color and formatting, convenience methods for
 * prompting the user, and the ability to interact with stdin/stdout/stderr
 * in some other process instead of this one.
 *
 * @task  construct   Construction
 * @task  interface   Interfacing with the User
 * @task  internal    Internals
 * @group console
 */
class PhutilConsole {

  private static $console;

  private $server;
  private $channel;
  private $messages = array();

  private $flushing = false;
  private $disabledTypes;


/* -(  Console Construction  )----------------------------------------------- */


  /**
   * Use @{method:newLocalConsole} or @{method:newRemoteConsole} to construct
   * new consoles.
   *
   * @task construct
   */
  private function __construct() {
    $this->disabledTypes = new \Facebook\Libphutil\PhutilArrayWithDefaultValue();
  }


  /**
   * Get the current console. If there's no active console, a new local console
   * is created (see @{method:newLocalConsole} for details). You can change the
   * active console with @{method:setConsole}.
   *
   * @return \Facebook\Libphutil\PhutilConsole  Active console.
   * @task construct
   */
  public static function getConsole() {
    if (empty(self::$console)) {
      self::setConsole(self::newLocalConsole());
    }
    return self::$console;
  }


  /**
   * Set the active console.
   *
   * @param \Facebook\Libphutil\PhutilConsole
   * @return void
   * @task construct
   */
  public static function setConsole(\Facebook\Libphutil\PhutilConsole $console) {
    self::$console = $console;
  }


  /**
   * Create a new console attached to stdin/stdout/stderr of this process.
   * This is how consoles normally work -- for instance, writing output with
   * @{method:writeOut} prints directly to stdout. If you don't create a
   * console explicitly, a new local console is created for you.
   *
   * @return \Facebook\Libphutil\PhutilConsole A new console which operates on the pipes of this
   *                       process.
   * @task construct
   */
  public static function newLocalConsole() {
    return self::newConsoleForServer(new \Facebook\Libphutil\PhutilConsoleServer());
  }


  public static function newConsoleForServer(\Facebook\Libphutil\PhutilConsoleServer $server) {
    $console = new \Facebook\Libphutil\PhutilConsole();
    $console->server = $server;
    return $console;
  }


  public static function newRemoteConsole() {
    $io_channel = new \Facebook\Libphutil\PhutilSocketChannel(
      fopen('php://stdin', 'r'),
      fopen('php://stdout', 'w'));
    $protocol_channel = new \Facebook\Libphutil\PhutilPHPObjectProtocolChannel($io_channel);

    $console = new \Facebook\Libphutil\PhutilConsole();
    $console->channel = $protocol_channel;

    return $console;
  }


/* -(  Interfacing with the User  )------------------------------------------ */


  public function confirm($prompt, $default = false) {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_CONFIRM)
      ->setData(
        array(
          'prompt'  => $prompt,
          'default' => $default,
        ));

    $this->writeMessage($message);
    $response = $this->waitForMessage();

    return $response->getData();
  }

  public function prompt($prompt, $history = '') {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_PROMPT)
      ->setData(
        array(
          'prompt'  => $prompt,
          'history' => $history,
        ));

    $this->writeMessage($message);
    $response = $this->waitForMessage();

    return $response->getData();
  }

  public function sendMessage($data) {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())->setData($data);
    return $this->writeMessage($message);
  }

  public function writeOut($pattern /* , ... */) {
    $args = func_get_args();
    return $this->writeTextMessage(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_OUT, $args);
  }

  public function writeErr($pattern /* , ... */) {
    $args = func_get_args();
    return $this->writeTextMessage(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR, $args);
  }

  public function writeLog($pattern /* , ... */) {
    $args = func_get_args();
    return $this->writeTextMessage(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_LOG, $args);
  }

  public function beginRedirectOut() {
    // We need as small buffer as possible. 0 means infinite, 1 means 4096 in
    // PHP < 5.4.0.
    ob_start(array($this, 'redirectOutCallback'), 2);
    $this->flushing = true;
  }

  public function endRedirectOut() {
    $this->flushing = false;
    ob_end_flush();
  }


/* -(  Internals  )---------------------------------------------------------- */

  // Must be public because it is called from output buffering.
  public function redirectOutCallback($string) {
    if (strlen($string)) {
      $this->flushing = false;
      $this->writeOut('%s', $string);
      $this->flushing = true;
    }
    return '';
  }

  private function writeTextMessage($type, array $argv) {

    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType($type)
      ->setData($argv);

    $this->writeMessage($message);

    return $this;
  }

  private function writeMessage(\Facebook\Libphutil\PhutilConsoleMessage $message) {
    if ($this->disabledTypes[$message->getType()]) {
      return $this;
    }

    if ($this->flushing) {
      ob_flush();
    }
    if ($this->channel) {
      $this->channel->write($message);
      $this->channel->flush();
    } else {
      $response = $this->server->handleMessage($message);
      if ($response) {
        $this->messages[] = $response;
      }
    }
    return $this;
  }

  private function waitForMessage() {
    if ($this->channel) {
      $message = $this->channel->waitForMessage();
    } else if ($this->messages) {
      $message = array_shift($this->messages);
    } else {
      throw new \Exception("waitForMessage() called with no messages!");
    }

    return $message;
  }

  public function getServer() {
    return $this->server;
  }

  private function disableMessageType($type) {
    $this->disabledTypes[$type] += 1;
    return $this;
  }

  private function enableMessageType($type) {
    if ($this->disabledTypes[$type] == 0) {
      throw new \Exception("Message type '{$type}' is already enabled!");
    }
    $this->disabledTypes[$type] -= 1;
    return $this;
  }

  public function disableOut() {
    return $this->disableMessageType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_OUT);
  }

  public function enableOut() {
    return $this->enableMessageType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_OUT);
  }

  public function isLogEnabled() {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_ENABLED)
      ->setData(
        array(
          'which' => \Facebook\Libphutil\PhutilConsoleMessage::TYPE_LOG,
        ));

    $this->writeMessage($message);
    $response = $this->waitForMessage();

    return $response->getData();
  }

  public function isErrATTY() {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_TTY)
      ->setData(
        array(
          'which' => \Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR,
        ));

    $this->writeMessage($message);
    $response = $this->waitForMessage();

    return $response->getData();
  }

  public function getErrCols() {
    $message = \Facebook\Libphutil\Functions\utils::id(new \Facebook\Libphutil\PhutilConsoleMessage())
      ->setType(\Facebook\Libphutil\PhutilConsoleMessage::TYPE_COLS)
      ->setData(
        array(
          'which' => \Facebook\Libphutil\PhutilConsoleMessage::TYPE_ERR,
        ));

    $this->writeMessage($message);
    $response = $this->waitForMessage();

    return $response->getData();
  }


}
