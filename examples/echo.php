<?php

declare(strict_types=1);

use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$infinite = new Infinite(new EventLoopBridge(), 1);
echo $infinite->run(static function (): string {
    sleep(1);

    return 'Hoi!';
}), PHP_EOL;

$infinite->close();
