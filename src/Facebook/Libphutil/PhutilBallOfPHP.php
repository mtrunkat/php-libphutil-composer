<?php

namespace Facebook\Libphutil;

/**
 * Concatenates PHP files together into a single stream. Used by Phage to
 * transmit bootloading code.
 */
class PhutilBallOfPHP {

  private $parts = array();

  public function addFile($path) {
    $data = \Facebook\Libphutil\Filesystem::readFile($path);
    if (strncmp($data, "<?php

namespace Facebook\Libphutil;\n", 6)) {
      throw new \Exception(
        "Expected file '{$path}' to begin \"<?php

namespace Facebook\Libphutil;\\n\".");
    }
    $this->parts[] = substr($data, 6);
    return $this;
  }

  public function addText($text) {
    $this->parts[] = $text;
  }

  public function toString() {
    return implode('', $this->parts);
  }

}
