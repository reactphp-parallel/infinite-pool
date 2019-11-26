<?php


use React\EventLoop\Factory;
use WyriHaximus\React\Parallel\Infinite;
use function React\Promise\all;
use WyriHaximus\React\Parallel\Finite;

$json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'large.json');

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Infinite($loop, 1);

$promises = [];
$signalHandler = function () use ($finite, $loop) {
    $loop->stop();
    $finite->close();
};

$tick = function () use (&$promises, $finite, $loop, $signalHandler, $json, &$tick) {
    if (count($promises) < 1000) {
        $promises[] = $finite->run(function($json) {
            $json = json_decode($json, true);
            return md5(json_encode($json));
        }, [$json]);
        $loop->futureTick($tick);
        return;
    }

    all($promises)->then(function ($v) {
        var_export($v);
    })->always(function () use ($finite, $loop, $signalHandler) {
        $finite->close();
        $loop->removeSignal(SIGINT, $signalHandler);
    })->done();

};
$loop->futureTick($tick);

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
