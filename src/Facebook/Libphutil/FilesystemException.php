<?php

namespace Facebook\Libphutil;

/**
 * \Exception thrown by \Facebook\Libphutil\Filesystem to indicate an error accessing the file
 * system.
 *
 * @group filesystem
 */
class FilesystemException extends \Exception {

  protected $path;

  /**
   * Create a new \Facebook\Libphutil\FilesystemException, providing a path and a message.
   *
   * @param  string  Path that caused the failure.
   * @param  string  Description of the failure.
   */
  public function __construct($path, $message) {
    $this->path = $path;
    parent::__construct($message);
  }


  /**
   * Retrieve the path associated with the exception. Generally, this is
   * something like a path that couldn't be read or written, or a path that
   * was expected to exist but didn't.
   *
   * @return string  Path associated with the exception.
   */
  public function getPath() {
    return $this->path;
  }

}
