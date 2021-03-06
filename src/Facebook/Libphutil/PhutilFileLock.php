<?php

namespace Facebook\Libphutil;

/**
 * Wrapper around `flock()` for advisory filesystem locking. Usage is
 * straightforward:
 *
 *   $path = '/path/to/lock.file';
 *   $lock = \Facebook\Libphutil\PhutilFileLock::newForPath($path);
 *   $lock->lock();
 *
 *     do_contentious_things();
 *
 *   $lock->unlock();
 *
 * For more information on locks, see @{class:PhutilLock}.
 *
 * @task  construct   Constructing Locks
 * @task  impl        Implementation
 *
 * @group filesystem
 */
class PhutilFileLock extends \Facebook\Libphutil\PhutilLock {

  private $lockfile;
  private $handle;


/* -(  Constructing Locks  )------------------------------------------------- */


  /**
   * Create a new lock on a lockfile. The file need not exist yet.
   *
   * @param   string          The lockfile to use.
   * @return  \Facebook\Libphutil\PhutilFileLock  New lock object.
   *
   * @task construct
   */
  public static function newForPath($lockfile) {
    $lockfile = \Facebook\Libphutil\Filesystem::resolvePath($lockfile);

    $name = 'file:'.$lockfile;
    $lock = self::getLock($name);
    if (!$lock) {
      $lock = new \Facebook\Libphutil\PhutilFileLock($name);
      $lock->lockfile = $lockfile;
      self::registerLock($lock);
    }

    return $lock;
  }

/* -(  Locking  )------------------------------------------------------------ */


  /**
   * Acquire the lock. If lock acquisition fails because the lock is held by
   * another process, throws @{class:PhutilLockException}. Other exceptions
   * indicate that lock acquisition has failed for reasons unrelated to locking.
   *
   * If the lock is already held, this method throws. You can test the lock
   * status with @{method:isLocked}.
   *
   * @param  float  Seconds to block waiting for the lock.
   * @return void
   *
   * @task lock
   */
  protected function doLock($wait) {
    $path = $this->lockfile;

    $handle = @fopen($path, 'a+');
    if (!$handle) {
      throw new \Facebook\Libphutil\FilesystemException(
        $path,
        "Unable to open lock '{$path}' for writing!");
    }

    $start_time = microtime(true);
    do {
      $would_block = null;
      $ok = flock($handle, LOCK_EX | LOCK_NB, $would_block);
      if ($ok) {
        break;
      } else {
        usleep(10000);
      }
    } while ($wait && $wait > (microtime(true) - $start_time));

    if (!$ok) {
      fclose($handle);
      throw new \Facebook\Libphutil\PhutilLockException($this->getName());
    }

    $this->handle = $handle;
  }


  /**
   * Release the lock. Throws an exception on failure, e.g. if the lock is not
   * currently held.
   *
   * @return void
   *
   * @task lock
   */
  protected function doUnlock() {
    $ok = flock($this->handle, LOCK_UN | LOCK_NB);
    if (!$ok) {
      throw new \Exception("Unable to unlock file!");
    }

    $ok = fclose($this->handle);
    if (!$ok) {
      throw new \Exception("Unable to close file!");
    }

    $this->handle = null;
  }
}
