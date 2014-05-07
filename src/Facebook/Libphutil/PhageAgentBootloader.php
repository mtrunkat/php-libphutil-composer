<?php

namespace Facebook\Libphutil;

abstract class PhageAgentBootloader {

  abstract public function getName();
  abstract public function getBootCommand();
  abstract public function getBootSequence();

}
