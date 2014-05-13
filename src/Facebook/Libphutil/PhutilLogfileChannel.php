<?php

namespace Facebook\Libphutil;

/**
 * A @{class:PhutilChannelChannel} which wraps some other channel and writes
 * data passed over it to a log file.
 */
class PhutilLogfileChannel extends \Facebook\Libphutil\PhutilChannelChannel {

  private $logfile;

  public function setLogfile($path) {
    $this->logfile = fopen($path, 'a');
    $this->log('--- '.getmypid().' ---');
    return $this;
  }

  public function read() {
    $buffer = parent::read();
    $this->log('>>> '.\Facebook\Libphutil\Functions\utils::phutil_loggable_string($buffer));
    return $buffer;
  }

  public function write($message) {
    $this->log('<<< '.\Facebook\Libphutil\Functions\utils::phutil_loggable_string($message));
    return parent::write($message);
  }

  private function log($message) {
    if ($this->logfile) {
      fwrite($this->logfile, $message."\n");
    }
  }

}
