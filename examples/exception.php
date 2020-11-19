<?php

use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$infinite = new Infinite($loop, new EventLoopBridge($loop), 1);

$infinite->run(function () {
    throw new RuntimeException('Whoops I did it again!');

    return 'We shouldn\'t reach this!';
})->always(function () use ($infinite, $loop) {
    $infinite->close();
    $loop->stop();
})->then(function (string $oops) {
    echo $oops, PHP_EOL;
}, function (Throwable $error) {
    echo $error, PHP_EOL;
})->done();

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
