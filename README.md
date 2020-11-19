# reactphp-parallel-infinite-pool

![Continuous Integration](https://github.com/Reactphp-parallel/infinite-pool/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/React-parallel/infinite-pool/v/stable.png)](https://packagist.org/packages/React-parallel/infinite-pool)
[![Total Downloads](https://poser.pugx.org/React-parallel/infinite-pool/downloads.png)](https://packagist.org/packages/React-parallel/infinite-pool)
[![Code Coverage](https://scrutinizer-ci.com/g/Reactphp-parallel/infinite-pool/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Reactphp-parallel/infinite-pool/?branch=master)
[![Type Coverage](https://shepherd.dev/github/Reactphp-parallel/infinite-pool/coverage.svg)](https://shepherd.dev/github/Reactphp-parallel/infinite-pool)
[![License](https://poser.pugx.org/React-parallel/infinite-pool/license.png)](https://packagist.org/packages/React-parallel/infinite-pool)

ReactPHP bindings around ext-parallel-infinite-pool

## Install ##

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require react-parallel/infinite-pool 
```

## Usage ##

The following example will spin up a thread with a 1 second TTL clean up policy. Meaning that threads are kept around 
for 1 second waiting for something to do before closed. It then runs a closure in the thread that will wait for one 
second before returning an message. Upon receiving that message the mean thread will echo out that message before 
closing the pool;

```php
use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

$loop = Factory::create();
$infinite = new Infinite($loop, new EventLoopBridge($loop), 1);
$infinite->run(function () {
    sleep(1);

    return 'Hoi!';
})->then(function (string $message) use ($infinite) {
    echo $message, PHP_EOL;
    $infinite->close();
});
$loop->run();
```

## License ##

Copyright 2020 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
