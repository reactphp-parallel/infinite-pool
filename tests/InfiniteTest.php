<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;
use ReactParallel\Pool\Infinite\Metrics;
use ReactParallel\Tests\AbstractPoolTest;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\Metrics\Printer\Prometheus;
use WyriHaximus\PoolInfo\Info;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;

use function sleep;

final class InfiniteTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    #[Test]
    public function withAZeroTTLThreadsShouldBeKilledOffImmidetally(): void
    {
        $registry = MetricsFactory::create();
        $pool     = new Infinite(new EventLoopBridge(), 0.0)->withMetrics(Metrics::create($registry));

        Loop::addTimer(1, static function () use ($pool, $registry): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], [...$pool->info()]);

            $metrics = $registry->print(new Prometheus());
            self::assertStringContainsString('react_parallel_pool_infinite_threads{state="idle"} 0', $metrics);
            self::assertStringContainsString('react_parallel_pool_infinite_threads{state="busy"} 1', $metrics);
        });

        $metrics = $registry->print(new Prometheus());
        self::assertSame("\n\n# EOF\n", $metrics);

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], [...$pool->info()]);

        $asteriks = $pool->run(static function (): int {
            sleep(3);

            return 42;
        });

        $metrics = $registry->print(new Prometheus());
        self::assertStringContainsString('react_parallel_pool_infinite_threads{state="idle"} 0', $metrics);
        self::assertStringContainsString('react_parallel_pool_infinite_threads{state="busy"} 0', $metrics);
        self::assertStringContainsString('react_parallel_pool_infinite_execution_time{quantile="0.1"} 3.0', $metrics);
        self::assertStringContainsString('react_parallel_pool_infinite_execution_time{quantile="0.5"} 3.0', $metrics);
        self::assertStringContainsString('react_parallel_pool_infinite_execution_time{quantile="0.9"} 3.0', $metrics);
        self::assertStringContainsString('react_parallel_pool_infinite_execution_time{quantile="0.99"} 3.0', $metrics);

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], [...$pool->info()]);

        $pool->kill();
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertSame(42, $asteriks);
    }

    #[Test]
    public function withAnAlmostZeroTTLThreadsShouldNotBeKilledOffImmidetally(): void
    {
        $pool = new Infinite(new EventLoopBridge(), 5)->withMetrics(Metrics::create(MetricsFactory::create()));

        Loop::addTimer(1, static function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], [...$pool->info()]);
        });

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], [...$pool->info()]);

        $asteriks = $pool->run(static function (): int {
            sleep(3);

            return 42;
        });

        self::assertSame([
            Info::TOTAL => 1,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 1,
            Info::SIZE  => 1,
        ], [...$pool->info()]);

        Loop::addTimer(0.5, static function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], [...$pool->info()]);
        });

        $asteriks = $pool->run(static function () use ($asteriks): int {
            sleep(1);

            return $asteriks;
        });

        self::assertSame([
            Info::TOTAL => 1,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 1,
            Info::SIZE  => 1,
        ], [...$pool->info()]);

        $pool->kill();
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertSame(42, $asteriks);
    }

    protected function poolFactory(): PoolInfoInterface
    {
        return new Infinite(new EventLoopBridge(), 5)->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    protected function createPool(): PoolInterface
    {
        return new Infinite(new EventLoopBridge(), 5)->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    #[Test]
    public function aquireLock(): void
    {
        $pool = new Infinite(new EventLoopBridge(), 5)->withMetrics(Metrics::create(MetricsFactory::create()));

        $group = $pool->acquireGroup();
        self::assertFalse($pool->close());
        self::assertFalse($pool->kill());

        $pool->releaseGroup($group);
        self::assertTrue($pool->close());
        self::assertTrue($pool->kill());
    }

    #[Test]
    public function withMetrics(): void
    {
        $pool            = new Infinite(new EventLoopBridge(), 5);
        $poolWithmetrics = $pool->withMetrics(Metrics::create(MetricsFactory::create()));

        self::assertNotSame($pool, $poolWithmetrics);
    }
}
