<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Parallel;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function WyriHaximus\iteratorOrArrayToArray;
use WyriHaximus\PoolInfo\Info;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;
use WyriHaximus\React\Parallel\Infinite;
use WyriHaximus\React\Parallel\PoolInterface;
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
        $pool = new Infinite($loop, 0.0);

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], iteratorOrArrayToArray($pool->info()));

        $pool->run(function () {
            sleep(3);

            return 42;
        })->then(function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 0,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 0,
                Info::SIZE  => 0,
            ], iteratorOrArrayToArray($pool->info()));
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

        $loop->run();
    }

    /**
     * @test
     */
    public function withAnAlmostZeroTTLThreadsShouldNotBeKilledOffImmidetally(): void
    {
        $loop = Factory::create();
        $pool = new Infinite($loop, 5);

        self::assertSame([
            Info::TOTAL => 0,
            Info::BUSY => 0,
            Info::CALLS => 0,
            Info::IDLE  => 0,
            Info::SIZE  => 0,
        ], iteratorOrArrayToArray($pool->info()));

        $pool->run(function () {
            sleep(3);

            return 42;
        })->then(function () use ($pool): PromiseInterface {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 1,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));

            $promise= $pool->run(function () {
                sleep(1);
            });

            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 1,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));

            return $promise;
        })->then(function () use ($pool): void {
            self::assertSame([
                Info::TOTAL => 1,
                Info::BUSY => 0,
                Info::CALLS => 0,
                Info::IDLE  => 1,
                Info::SIZE  => 1,
            ], iteratorOrArrayToArray($pool->info()));
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

        $loop->run();
    }

    protected function poolFactory(): PoolInfoInterface
    {
        return new Infinite(Factory::create(), 5);
    }

    protected function createPool(LoopInterface $loop): PoolInterface
    {
        return new Infinite($loop, 5);
    }

    /**
     * @test
     */
    public function aquireLock(): void
    {
        $pool = new Infinite(Factory::create(), 5);

        $group = $pool->acquireGroup();
        self::assertFalse($pool->close());
        self::assertFalse($pool->kill());

        $pool->releaseGroup($group);
        self::assertTrue($pool->close());
        self::assertTrue($pool->kill());
    }
}
