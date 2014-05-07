<?php

namespace Facebook\Libphutil;

/**
 * @group aphront
 */
final class AphrontScopedUnguardedWriteCapability {

  final public function __destruct() {
    \Facebook\Libphutil\AphrontWriteGuard::endUnguardedWrites();
  }

}
