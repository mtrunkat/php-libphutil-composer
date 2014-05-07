<?php

namespace Facebook\Libphutil;

/**
 * @group console
 */
final class PhutilHelpArgumentWorkflow extends \Facebook\Libphutil\PhutilArgumentWorkflow {

  protected function didConstruct() {
    $this->setName('help');
    $this->setExamples(<<<EOHELP
**help** [__command__]
EOHELP
);
    $this->setSynopsis(<<<EOHELP
Show this help, or workflow help for __command__.
EOHELP
      );
    $this->setArguments(
      array(
        array(
          'name'      => 'help-with-what',
          'wildcard'  => true,
        )));
  }

  public function isExecutable() {
    return true;
  }

  public function execute(\Facebook\Libphutil\PhutilArgumentParser $args) {
    $with = $args->getArg('help-with-what');

    if (!$with) {
      $args->printHelpAndExit();
    } else {
      foreach ($with as $thing) {
        echo \Facebook\Libphutil\Functions\format::phutil_console_format(
          "**%s WORKFLOW**\n\n",
          strtoupper($thing));
        echo $args->renderWorkflowHelp($thing, $show_flags = true);
        echo "\n";
      }
      exit(\Facebook\Libphutil\PhutilArgumentParser::PARSE_ERROR_CODE);
    }
  }

}
