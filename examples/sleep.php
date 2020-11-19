<?php

use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

use function React\Promise\all;
use function WyriHaximus\iteratorOrArrayToArray;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$infinite = new Infinite($loop, new EventLoopBridge($loop), 0.1);

$timer = $loop->addPeriodicTimer(1, function () use ($infinite) {
    var_export(iteratorOrArrayToArray($infinite->info()));
});

$promises = [];
foreach (range(0, 250) as $i) {
    $promises[] = $infinite->run(function($sleep) {
        sleep($sleep);
        return $sleep;
    }, [random_int(1, 13)])->then(function (int $sleep) use ($i) {
        echo $i, '; ', $sleep, PHP_EOL;

        return $sleep;
    });
}

$signalHandler = function () use ($infinite, $loop) {
    $loop->stop();
    $infinite->close();
};
all($promises)->then(function ($v) use ($infinite, $loop, $signalHandler, $timer) {
    $infinite->close();
    $loop->removeSignal(SIGINT, $signalHandler);
    $loop->cancelTimer($timer);
    $loop->stop();
})->done();

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
