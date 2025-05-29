<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Infinite;

use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Pool\Infinite\Metrics;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\TestUtilities\TestCase;

final class MetricsTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $metrics = Metrics::create(MetricsFactory::create());

        /** @phpstan-ignore method.deprecated */
        self::assertSame($metrics->executionTime, $metrics->executionTime());
        /** @phpstan-ignore method.deprecated */
        self::assertSame($metrics->threads, $metrics->threads());
    }
}
