<?php

namespace Facebook\Libphutil;

/**
 * @group markup
 */
class PhutilXHPASTSyntaxHighlighter {

  public function getHighlightFuture($source) {
    $scrub = false;
    if (strpos($source, '<?') === false) {
      $source = "<?php

namespace Facebook\Libphutil;\n".$source."\n";
      $scrub = true;
    }

    return new \Facebook\Libphutil\PhutilXHPASTSyntaxHighlighterFuture(
      \Facebook\Libphutil\Functions\xhpast_parse::xhpast_get_parser_future($source),
      $source,
      $scrub);
  }

}
