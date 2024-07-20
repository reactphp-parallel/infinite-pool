<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use React\EventLoop\Loop;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$finite = new Infinite(new EventLoopBridge(), 0.1);

Loop::addTimer(1, static function () use ($finite): void {
    $finite->kill();
    Loop::stop();
});

var_export(
    $finite->run(
        static fn (): array => array_merge(
            ...array_map(
                static fn (string $package): array => [
                    $package => InstalledVersions::getPrettyVersion($package),
                ],
                InstalledVersions::getInstalledPackages(),
            )
        )
    )
);
