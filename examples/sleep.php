<?php

declare(strict_types=1);

use React\EventLoop\Loop;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

use function React\Async\async;
use function React\Promise\all;
use function WyriHaximus\iteratorOrArrayToArray;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$infinite = new Infinite(new EventLoopBridge(), 0.1);

$timer = Loop::addPeriodicTimer(1, static function () use ($infinite): void {
    var_export(iteratorOrArrayToArray($infinite->info()));
});

$promises = [];
foreach (range(0, 250) as $i) {
    $promises[] = async(static fn (int $i): int => $infinite->run(static function (int $sleep): int {
        sleep($sleep);

        return $sleep;
    }, [random_int(1, 13)])->then(static function (int $sleep) use ($i): int {
        echo $i, '; ', $sleep, PHP_EOL;

        return $sleep;
    }))($i);
}

$signalHandler = static function () use ($infinite): void {
    Loop::stop();
    $infinite->close();
};
all($promises)->then(static function () use ($infinite, $signalHandler, $timer): void {
    $infinite->close();
    Loop::removeSignal(SIGINT, $signalHandler);
    Loop::cancelTimer($timer);
    Loop::stop();
})->done();

Loop::addSignal(SIGINT, $signalHandler);
