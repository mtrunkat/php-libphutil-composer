<?php

namespace Facebook\Libphutil;

/**
 * Concrete subclass of @{class:AbstractDirectedGraph} which can not load any
 * data from external sources.
 */
class PhutilDirectedScalarGraph extends \Facebook\Libphutil\AbstractDirectedGraph {

  protected function loadEdges(array $nodes) {
    throw new \Exception(
      "\Facebook\Libphutil\PhutilDirectedScalarGraph can not load additional nodes at runtime. ".
      "Tried to load: ".implode(', ', $nodes));
  }

}
