<?php

use React\EventLoop\Factory;
use WyriHaximus\React\Parallel\Infinite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Infinite($loop, 1);

$finite->run(function () {
    throw new RuntimeException('Whoops I did it again!');

    return 'We shouldn\'t reach this!';
})->always(function () use ($finite) {
    $finite->close();
})->then(function (string $oops) {
    echo $oops, PHP_EOL;
}, function (Throwable $error) {
    echo $error, PHP_EOL;
})->done();

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;