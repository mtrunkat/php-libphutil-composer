<?php

namespace Facebook\Libphutil;

/**
 * Represents current transaction state of a connection.
 *
 * @group storage
 */
class AphrontDatabaseTransactionState {

  private $depth           = 0;
  private $readLockLevel   = 0;
  private $writeLockLevel  = 0;

  public function getDepth() {
    return $this->depth;
  }

  public function increaseDepth() {
    return ++$this->depth;
  }

  public function decreaseDepth() {
    if ($this->depth == 0) {
      throw new \Exception(
        'Too many calls to saveTransaction() or killTransaction()!');
    }

    return --$this->depth;
  }

  public function getSavepointName() {
    return 'Aphront_Savepoint_'.$this->depth;
  }

  public function beginReadLocking() {
    $this->readLockLevel++;
    return $this;
  }

  public function endReadLocking() {
    if ($this->readLockLevel == 0) {
      throw new \Exception("Too many calls to endReadLocking()!");
    }
    $this->readLockLevel--;
    return $this;
  }

  public function isReadLocking() {
    return ($this->readLockLevel > 0);
  }

  public function beginWriteLocking() {
    $this->writeLockLevel++;
    return $this;
  }

  public function endWriteLocking() {
    if ($this->writeLockLevel == 0) {
      throw new \Exception("Too many calls to endWriteLocking()!");
    }
    $this->writeLockLevel--;
    return $this;
  }

  public function isWriteLocking() {
    return ($this->writeLockLevel > 0);
  }

  public function __destruct() {
    if ($this->depth) {
      throw new \Exception(
        'Process exited with an open transaction! The transaction will be '.
        'implicitly rolled back. Calls to openTransaction() must always be '.
        'paired with a call to saveTransaction() or killTransaction().');
    }
    if ($this->readLockLevel) {
      throw new \Exception(
        'Process exited with an open read lock! Call to beginReadLocking() '.
        'must always be paired with a call to endReadLocking().');
    }
    if ($this->writeLockLevel) {
      throw new \Exception(
        'Process exited with an open write lock! Call to beginWriteLocking() '.
        'must always be paired with a call to endWriteLocking().');
    }
  }

}
