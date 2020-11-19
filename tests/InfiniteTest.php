<?php declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use WyriHaximus\Metrics\Factory as MetricsFactory;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Infinite;
use ReactParallel\Pool\Infinite\Metrics;
use ReactParallel\Tests\AbstractPoolTest;
use WyriHaximus\PoolInfo\Info;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;
use function WyriHaximus\iteratorOrArrayToArray;
use function Safe\sleep;

/**
 * @internal
 */
final class InfiniteTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    /**
     * @test
     */
    public function withAZeroTTLThreadsShouldBeKilledOffImmidetally(): void
    {
        $loop = Factory::create();
        $pool = $pool = (new Infinite($loop, new EventLoopBridge($loop), 0.0))->withMetrics(Metrics::create(MetricsFactory::create()));

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], iteratorOrArrayToArray($pool->info()));

        $promise = $pool->run(function (): int {
            sleep(3);

            return 42;
        })->then(function (int $asteriks) use ($pool): int {
            self::assertSame([
                Info::TOTAL => 0,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 0,
            ], iteratorOrArrayToArray($pool->info()));

            return $asteriks;
        });

        $loop->addTimer(1, function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));
        });

        self::assertSame(42, $this->await($promise, $loop, 13));
        $pool->kill();
    }

    /**
     * @test
     */
    public function withAnAlmostZeroTTLThreadsShouldNotBeKilledOffImmidetally(): void
    {
        $loop = Factory::create();
        $pool = $pool = (new Infinite($loop, new EventLoopBridge($loop), 5))->withMetrics(Metrics::create(MetricsFactory::create()));

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], iteratorOrArrayToArray($pool->info()));

        $promise = $pool->run(function (): int {
            sleep(3);

            return 42;
        })->then(function (int $asteriks) use ($pool): PromiseInterface {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 1,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));

            $promise= $pool->run(function () use ($asteriks): int {
                sleep(1);

                return $asteriks;
            });

            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));

            return $promise;
        })->then(function (int $asteriks) use ($pool): int {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 1,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));

            return $asteriks;
        });

        $loop->addTimer(1, function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 1,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));
        });

        self::assertSame(42, $this->await($promise, $loop, 13));
        $pool->kill();
    }

    private function poolFactory(): PoolInfoInterface
    {
        $loop = Factory::create();
        return $pool = (new Infinite($loop, new EventLoopBridge($loop), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    protected function createPool(LoopInterface $loop): PoolInterface
    {
        return $pool = (new Infinite($loop, new EventLoopBridge($loop), 5))->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    /**
     * @test
     */
    public function aquireLock(): void
    {
        $loop = Factory::create();
        $pool = (new Infinite($loop, new EventLoopBridge($loop), 5))->withMetrics(Metrics::create(MetricsFactory::create()));

        $group = $pool->acquireGroup();
        self::assertFalse($pool->close());
        self::assertFalse($pool->kill());

        $pool->releaseGroup($group);
        self::assertTrue($pool->close());
        self::assertTrue($pool->kill());
    }
}
