<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Pool\Infinite\Group;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function strlen;

final class GroupTest extends AsyncTestCase
{
    /** @return iterable<array{0: ?int<1, max>, 1: int}> */
    public static function bytes(): iterable
    {
        yield [null, 64];
        yield [13, 26];
        yield [32, 64];
        yield [16, 32];
    }

    #[Test]
    public function create(): void
    {
        self::assertSame(64, strlen((string) Group::create()));
    }

    /** @param ?int<1, max> $bytes */
    #[Test]
    #[DataProvider('bytes')]
    public function createWithBytes(int|null $bytes, int $expected): void
    {
        self::assertSame($expected, strlen((string) Group::create($bytes)));
    }
}
