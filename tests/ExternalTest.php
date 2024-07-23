<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;
use ReactParallel\Pool\Infinite\Metrics;
use ReactParallel\Tests\AbstractPoolTest;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;

// phpcs:disable
if (InstalledVersions::satisfies(new VersionParser(), 'wyrihaximus/pool-info', '^2')) {
    final class ExternalTest extends AbstractPoolTest
    {
        use PoolInfoTestTrait;

        /** @phpstan-ignore-next-line */
        private function poolFactory(): PoolInfoInterface
        {
            return (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
        }

        protected function createPool(): PoolInterface
        {
            return (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
        }
    }
} else {
    final class ExternalTest
    {
        /** @test */
        public function aquireLock(): void
        {
            $pool = (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));

            $group = $pool->acquireGroup();
            self::assertFalse($pool->close());
            self::assertFalse($pool->kill());

            $pool->releaseGroup($group);
            self::assertTrue($pool->close());
            self::assertTrue($pool->kill());
        }
    }
}
// phpcs:enable
