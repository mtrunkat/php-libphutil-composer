<?php

namespace Facebook\Libphutil;

class PhutilCommandString extends \Facebook\Libphutil\Phobject {

  private $argv;

  public function __construct(array $argv) {
    $this->argv = $argv;

    // This makes sure we throw immediately if there are errors in the
    // parameters.
    $this->getMaskedString();
  }

  public function __toString() {
    return $this->getMaskedString();
  }

  public function getUnmaskedString() {
    return $this->renderString($unmasked = true);
  }

  public function getMaskedString() {
    return $this->renderString($unmasked = false);
  }

  private function renderString($unmasked) {
    return \Facebook\Libphutil\Functions\xsprintf::xsprintf(
      '\Facebook\Libphutil\Functions\csprintf::xsprintf_command',
      array(
        'unmasked' => $unmasked,
      ),
      $this->argv);
  }

}
