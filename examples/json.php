<?php


use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use function React\Promise\all;
use ReactParallel\Pool\Infinite\Infinite;

$json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'large.json');

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$infinite = new Infinite($loop, new EventLoopBridge($loop), 1);

$promises = [];
$signalHandler = function () use ($infinite, $loop) {
    $loop->stop();
    $infinite->close();
};

$tick = function () use (&$promises, $infinite, $loop, $signalHandler, $json, &$tick) {
    if (count($promises) < 1000) {
        $promises[] = $infinite->run(function($json) {
            $json = json_decode($json, true);
            return md5(json_encode($json));
        }, [$json]);
        $loop->futureTick($tick);
        return;
    }

    all($promises)->then(function ($v) {
        var_export($v);
    })->always(function () use ($infinite, $loop, $signalHandler) {
        $infinite->close();
        $loop->removeSignal(SIGINT, $signalHandler);
        $loop->stop();
    })->done();

};
$loop->futureTick($tick);

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
