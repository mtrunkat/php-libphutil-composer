<?php

namespace Facebook\Libphutil;

/**
 * This class provides several approaches for querying data from the database:
 *
 *   # Async queries: Used under MySQLi with MySQLnd.
 *   # Parallel queries: Used under HPHP.
 *   # Multi queries: Used under MySQLi or HPHP.
 *   # Single queries: Used under MySQL.
 *
 * The class automatically decides which approach to use. Usage is like with
 * other futures:
 *
 *   $futures = array();
 *   $futures[] = new \Facebook\Libphutil\QueryFuture($conn1, 'SELECT 1');
 *   $futures[] = new \Facebook\Libphutil\QueryFuture($conn1, 'DELETE FROM table');
 *   $futures[] = new \Facebook\Libphutil\QueryFuture($conn2, 'SELECT 2');
 *
 *   foreach (\Facebook\Libphutil\Functions\functions::Futures($futures) as $future) {
 *     try {
 *       $result = $future->resolve();
 *     } catch (\Facebook\Libphutil\AphrontQueryException $ex) {
 *     }
 *   }
 *
 * `$result` contains a list of dicts for select queries or number of modified
 * rows for modification queries.
 */
class QueryFuture extends \Facebook\Libphutil\Future {

  private static $futures = array();

  private $conn;
  private $query;
  private $id;
  private $async;
  private $profilerCallID;

  public function __construct(
    \Facebook\Libphutil\AphrontDatabaseConnection $conn,
    $pattern/* , ... */) {

    $this->conn = $conn;

    $args = func_get_args();
    $args = array_slice($args, 2);
    $this->query = \Facebook\Libphutil\Functions\qsprintf::vqsprintf($conn, $pattern, $args);

    self::$futures[] = $this;
    $this->id = \Facebook\Libphutil\Functions\utils::last_key(self::$futures);
  }

  public function isReady() {
    if ($this->result !== null || $this->exception) {
      return true;
    }

    if (!$this->conn->supportsAsyncQueries()) {
      if ($this->conn->supportsParallelQueries()) {
        $queries = array();
        $conns = array();
        foreach (self::$futures as $id => $future) {
          $queries[$id] = $future->query;
          $conns[$id] = $future->conn;
        }
        $results = $this->conn->executeParallelQueries($queries, $conns);
        $this->processResults($results);
        return true;
      }

      $conns = array();
      $conn_queries = array();
      foreach (self::$futures as $id => $future) {
        $hash = spl_object_hash($future->conn);
        $conns[$hash] = $future->conn;
        $conn_queries[$hash][$id] = $future->query;
      }
      foreach ($conn_queries as $hash => $queries) {
        $this->processResults($conns[$hash]->executeRawQueries($queries));
      }
      return true;
    }

    if (!$this->async) {
      $profiler = \Facebook\Libphutil\PhutilServiceProfiler::getInstance();
      $this->profilerCallID = $profiler->beginServiceCall(
        array(
          'type'    => 'query',
          'query'   => $this->query,
          'async'   => true,
        ));

      $this->async = $this->conn->asyncQuery($this->query);
      return false;
    }

    $conns = array();
    $asyncs = array();
    foreach (self::$futures as $id => $future) {
      if ($future->async) {
        $conns[$id] = $future->conn;
        $asyncs[$id] = $future->async;
      }
    }

    $this->processResults($this->conn->resolveAsyncQueries($conns, $asyncs));

    if ($this->result !== null || $this->exception) {
      return true;
    }
    return false;
  }

  private function processResults(array $results) {
    foreach ($results as $id => $result) {
      $future = self::$futures[$id];
      if ($result instanceof \Exception) {
        $future->exception = $result;
      } else {
        $future->result = $result;
      }
      unset(self::$futures[$id]);
      if ($future->profilerCallID) {
        $profiler = \Facebook\Libphutil\PhutilServiceProfiler::getInstance();
        $profiler->endServiceCall($future->profilerCallID, array());
      }
    }
  }
}
