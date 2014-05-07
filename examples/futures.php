<?php

// Example use of Futures
// You have to first download autoloader using Composer.

require_once 'vendor/autoload.php';

use Facebook\Libphutil\ExecFuture;
use Facebook\Libphutil\Functions\functions;

$futures = array();
$futures['test a'] = new ExecFuture('ls');
$futures['test b'] = new ExecFuture('ls -l -a');

foreach (functions::Futures($futures) as $dir => $future) {
    list($stdout, $stderr) = $future->resolvex();

    print $stdout;
}