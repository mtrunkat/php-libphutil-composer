Composer compatible version of Facebook's Libphutil library
------
This is not offical port. I created this mainly to use [Futures]( https://secure.phabricator.com/book/libphutil/article/using_futures/) so the rest of the library may not work correctly.

Script that I use for conversion can be found here [mtrunkat/php-libphutil-composer-convertor](https://github.com/mtrunkat/php-libphutil-composer-convertor). 

For more information about Libphutil check [Github repository](https://github.com/facebook/libphutil) and official [documentation](https://secure.phabricator.com/book/libphutil/).

### Installation

Add this library into you composer.json:
```json
{
    "require" : {
        "trunkat/lbphutil" : "dev-master"
    }
}
```
And install it via `php composer.phar install` or `php composer.phar update`.

### Example use

All the **classes** of original library are moved into **Facebook\Libphutil namespace** and each **function [functionname]** located in some file [filename].php is converted into static method **Facebook\Libphutil\Functions[filename]::[functionname]**.

So for example in original library you use Futures this way:
```php
<?php
	
require_once 'path/to/libphutil/src/__phutil_library_init__.php';

$futures = array();
$futures['test a'] = new ExecFuture('ls');
$futures['test b'] = new ExecFuture('ls -l -a');
	
foreach (Futures($futures) as $dir => $future) {
    list($stdout, $stderr) = $future->resolvex();
	
    print $stdout;
}
```
But in Composer version of library you have to specify namespace for classes and convert functions into static methods:
```php
<?php

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
```

You can see that class **ExecFuture** in now in **Facebook\Libphutil** namespace and function **Futures()** originally located in file **functions.php** is now static method of class **Facebook\Libphutil\Functions\functions**.
