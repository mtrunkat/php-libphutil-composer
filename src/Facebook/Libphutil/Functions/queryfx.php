<?php

namespace Facebook\Libphutil\Functions;

class queryfx {
  
  /**
   * @group storage
   */
  static function queryfx(\Facebook\Libphutil\AphrontDatabaseConnection $conn, $sql/* , ... */) {
    $argv = func_get_args();
    $query = call_user_func_array('\Facebook\Libphutil\Functions\qsprintf::qsprintf', $argv);
    $conn->executeRawQuery($query);
  }
  
  /**
   * @group storage
   */
  static function vqueryfx(\Facebook\Libphutil\AphrontDatabaseConnection $conn, $sql, array $argv) {
    array_unshift($argv, $conn, $sql);
    call_user_func_array('\Facebook\Libphutil\Functions\queryfx::queryfx', $argv);
  }
  
  /**
   * @group storage
   */
  static function queryfx_all(\Facebook\Libphutil\AphrontDatabaseConnection $conn, $sql/* , ... */) {
    $argv = func_get_args();
    call_user_func_array('\Facebook\Libphutil\Functions\queryfx::queryfx', $argv);
    return $conn->selectAllResults();
  }
  
  /**
   * @group storage
   */
  static function queryfx_one(\Facebook\Libphutil\AphrontDatabaseConnection $conn, $sql/* , ... */) {
    $argv = func_get_args();
    $ret = call_user_func_array('\Facebook\Libphutil\Functions\queryfx::queryfx_all', $argv);
    if (count($ret) > 1) {
      throw new \Facebook\Libphutil\AphrontQueryCountException(
        'Query returned more than one row.');
    } else if (count($ret)) {
      return reset($ret);
    }
    return null;
  }
  
  /**
   * @group storage
   */
  static function vqueryfx_all(\Facebook\Libphutil\AphrontDatabaseConnection $conn, $sql, array $argv) {
    array_unshift($argv, $conn, $sql);
    call_user_func_array('\Facebook\Libphutil\Functions\queryfx::queryfx', $argv);
    return $conn->selectAllResults();
  }
  
}
