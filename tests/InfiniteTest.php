<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use React\EventLoop\Loop;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;
use ReactParallel\Pool\Infinite\Metrics;
use ReactParallel\Tests\AbstractPoolTest;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\PoolInfo\Info;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;

use function sleep;

final class InfiniteTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    /** @test */
    public function withAZeroTTLThreadsShouldBeKilledOffImmidetally(): void
    {
        $pool = (new Infinite(new EventLoopBridge(), 0.0))->withMetrics(Metrics::create(MetricsFactory::create()));

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
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], [...$pool->info()]);

        $pool->kill();
        self::assertSame(42, $asteriks); /** @phpstan-ignore-line */
    }

    /** @test */
    public function withAnAlmostZeroTTLThreadsShouldNotBeKilledOffImmidetally(): void
    {
        $pool = (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));

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
        self::assertSame(42, $asteriks); /** @phpstan-ignore-line */
    }

    /** @phpstan-ignore-next-line */
    private function poolFactory(): PoolInfoInterface
    {
        return (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    protected function createPool(): PoolInterface
    {
        return (new Infinite(new EventLoopBridge(), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
    }

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
