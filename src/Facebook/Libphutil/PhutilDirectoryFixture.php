<?php

namespace Facebook\Libphutil;

/**
 * @group filesystem
 */
final class PhutilDirectoryFixture {

  protected $path;

  public static function newFromArchive($archive) {
    $obj = self::newEmptyFixture();
    \Facebook\Libphutil\Functions\execx::execx(
      'tar -C %s -xzvvf %s',
      $obj->getPath(),
      \Facebook\Libphutil\Filesystem::resolvePath($archive));
    return $obj;
  }

  public static function newEmptyFixture() {
    $obj = new \Facebook\Libphutil\PhutilDirectoryFixture();
    $obj->path = \Facebook\Libphutil\Filesystem::createTemporaryDirectory();
    return $obj;
  }

  private function __construct() {
    // <restricted>
  }

  public function __destruct() {
    \Facebook\Libphutil\Filesystem::remove($this->path);
  }

  public function getPath($to_file = null) {
    return $this->path.'/'.ltrim($to_file, '/');
  }

  public function saveToArchive($path) {
    $tmp = new \Facebook\Libphutil\TempFile();

    \Facebook\Libphutil\Functions\execx::execx(
      'tar -C %s -czvvf %s .',
      $this->getPath(),
      $tmp);

    $ok = rename($tmp, \Facebook\Libphutil\Filesystem::resolvePath($path));
    if (!$ok) {
      throw new \Facebook\Libphutil\FilesystemException($path, 'Failed to overwrite file.');
    }

    return $this;
  }

}
