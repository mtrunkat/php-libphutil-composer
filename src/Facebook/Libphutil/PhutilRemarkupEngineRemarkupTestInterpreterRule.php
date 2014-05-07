<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
final class PhutilRemarkupEngineRemarkupTestInterpreterRule
  extends \Facebook\Libphutil\PhutilRemarkupBlockInterpreter {

  public function getInterpreterName() {
    return "phutil_test_block_interpreter";
  }

  public function markupContent($content, array $argv) {
    return sprintf(
      "Content: (%s)\nArgv: (%s)",
      $content,
      http_build_query($argv));
  }

}
