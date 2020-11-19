<?php

use Composer\InstalledVersions;
use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Infinite($loop, new EventLoopBridge($loop), 0.1);

$loop->addTimer(1, function () use ($finite, $loop) {
    $finite->kill();
    $loop->stop();
});
$finite->run(function (): array {
    return array_merge(...array_map(static fn (string $package): array => [$package => InstalledVersions::getPrettyVersion($package)], InstalledVersions::getInstalledPackages()));
})->then(function (array $versions): void {
    var_export($versions);
});

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
