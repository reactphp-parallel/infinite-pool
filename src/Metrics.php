<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Infinite;

use Deprecated;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\Registry;

/** @api */
final readonly class Metrics
{
    public function __construct(
        public Registry\Gauges $threads,
        public Registry\Summaries $executionTime,
    ) {
    }

    public static function create(Registry $registry): self
    {
        return new self(
            $registry->gauge(
                'react_parallel_pool_infinite_threads',
                'Currently active or idle thread count',
                new Name('state'),
            ),
            $registry->summary(
                'react_parallel_pool_infinite_execution_time',
                'Thread call execution time',
                MetricsFactory::defaultQuantiles(),
            ),
        );
    }

    #[Deprecated(message: 'Use threads property instead')]
    public function threads(): Registry\Gauges
    {
        return $this->threads;
    }

    #[Deprecated(message: 'Use executionTime property instead')]
    public function executionTime(): Registry\Summaries
    {
        return $this->executionTime;
    }
}
