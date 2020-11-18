<?php


use PackageVersions\Versions;
use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;
use function WyriHaximus\iteratorOrArrayToArray;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Infinite($loop, new EventLoopBridge($loop), 0.1);

$loop->addTimer(1, function () use ($finite) {
    $finite->kill();
});
$finite->run(function (): array {
    return Versions::VERSIONS;
})->then(function (array $versions): void {
    var_export($versions);
});

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;