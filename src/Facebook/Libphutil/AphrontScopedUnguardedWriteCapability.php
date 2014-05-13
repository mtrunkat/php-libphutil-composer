<?php

namespace Facebook\Libphutil;

/**
 * @group aphront
 */
class AphrontScopedUnguardedWriteCapability {

  final public function __destruct() {
    \Facebook\Libphutil\AphrontWriteGuard::endUnguardedWrites();
  }

}
