<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Infinite;

use Closure;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use ReactParallel\Contracts\ClosedException;
use ReactParallel\Contracts\GroupInterface;
use ReactParallel\Contracts\LowLevelPoolInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;
use WyriHaximus\Metrics\Label;
use WyriHaximus\PoolInfo\Info;

use function array_key_exists;
use function array_pop;
use function assert;
use function count;
use function dirname;
use function file_exists;
use function is_int;
use function React\Promise\reject;
use function Safe\hrtime;
use function spl_object_id;

use const DIRECTORY_SEPARATOR;
use const WyriHaximus\Constants\Boolean\FALSE_;
use const WyriHaximus\Constants\Boolean\TRUE_;

final class Infinite implements LowLevelPoolInterface
{
    private const AUTOLOADER_LEVELS = [2, 5];

    /** @var Runtime[] */
    private array $runtimes = [];

    /** @var string[] */
    private array $idleRuntimes = [];

    /** @var TimerInterface[] */
    private array $ttlTimers = [];

    private string $autoload;

    private Metrics|null $metrics = null;

    /** @var GroupInterface[] */
    private array $groups = [];

    private bool $closed = FALSE_;

    public function __construct(private EventLoopBridge $eventLoopBridge, private float $ttl)
    {
        $this->autoload = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        foreach (self::AUTOLOADER_LEVELS as $level) {
            $this->autoload = dirname(__FILE__, $level) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (file_exists($this->autoload)) {
                break;
            }
        }
    }

    public function withMetrics(Metrics $metrics): self
    {
        $self          = clone $this;
        $self->metrics = $metrics;

        return $self;
    }

    /** @param mixed[] $args */
    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        if ($this->closed === TRUE_) {
            return reject(ClosedException::create());
        }

        return (new Promise(function (callable $resolve, callable $reject): void {
            if (count($this->idleRuntimes) === 0) {
                $resolve($this->spawnRuntime());

                return;
            }

            $resolve($this->getIdleRuntime());
        }))->then(function (Runtime $runtime) use ($callable, $args): PromiseInterface {
            $time = null;
            if ($this->metrics instanceof Metrics) {
                $this->metrics->threads()->gauge(new Label('state', 'busy'))->incr();
                $this->metrics->threads()->gauge(new Label('state', 'idle'))->dcr();
                $time = hrtime(true);
            }

            /** @psalm-suppress UndefinedInterfaceMethod */
            return $runtime->run($callable, $args)->always(function () use ($runtime, $time): void {
                if ($this->metrics instanceof Metrics) {
                    $this->metrics->executionTime()->summary()->observe((hrtime(true) - $time) / 1e+9);
                    $this->metrics->threads()->gauge(new Label('state', 'idle'))->incr();
                    $this->metrics->threads()->gauge(new Label('state', 'busy'))->dcr();
                }

                if ($this->ttl >= 0.1) {
                    $this->addRuntimeToIdleList($runtime);
                    $this->startTtlTimer($runtime);

                    return;
                }

                $this->closeRuntime(spl_object_id($runtime));
            });
        });
    }

    public function close(): bool
    {
        if (count($this->groups) > 0) {
            return FALSE_;
        }

        $this->closed = TRUE_;

        foreach ($this->runtimes as $hash => $runtime) {
            $this->closeRuntime($hash);
        }

        return TRUE_;
    }

    public function kill(): bool
    {
        if (count($this->groups) > 0) {
            return FALSE_;
        }

        $this->closed = TRUE_;

        foreach ($this->runtimes as $runtime) {
            $runtime->kill();
        }

        return TRUE_;
    }

    /** @return iterable<string, int> */
    public function info(): iterable
    {
        yield Info::TOTAL => count($this->runtimes);
        yield Info::BUSY => count($this->runtimes) - count($this->idleRuntimes);
        yield Info::CALLS => 0;
        yield Info::IDLE  => count($this->idleRuntimes);
        yield Info::SIZE  => count($this->runtimes);
    }

    public function acquireGroup(): GroupInterface
    {
        $group                         = Group::create();
        $this->groups[(string) $group] = $group;

        return $group;
    }

    public function releaseGroup(GroupInterface $group): void
    {
        unset($this->groups[(string) $group]);
    }

    private function getIdleRuntime(): Runtime
    {
        $hash = array_pop($this->idleRuntimes);
        assert(is_int($hash));

        if (array_key_exists($hash, $this->ttlTimers)) {
            Loop::cancelTimer($this->ttlTimers[$hash]);
            unset($this->ttlTimers[$hash]);
        }

        return $this->runtimes[$hash];
    }

    private function addRuntimeToIdleList(Runtime $runtime): void
    {
        $hash                      = spl_object_id($runtime);
        $this->idleRuntimes[$hash] = $hash;
    }

    private function spawnRuntime(): Runtime
    {
        $runtime                                 = new Runtime($this->eventLoopBridge, $this->autoload);
        $this->runtimes[spl_object_id($runtime)] = $runtime;

        if ($this->metrics instanceof Metrics) {
            $this->metrics->threads()->gauge(new Label('state', 'idle'))->incr();
        }

        return $runtime;
    }

    private function startTtlTimer(Runtime $runtime): void
    {
        $hash = spl_object_id($runtime);

        $this->ttlTimers[$hash] = Loop::addTimer($this->ttl, function () use ($hash): void {
            $this->closeRuntime($hash);
        });
    }

    private function closeRuntime(int $hash): void
    {
        $runtime = $this->runtimes[$hash];
        $runtime->close();

        unset($this->runtimes[$hash]);

        if (array_key_exists($hash, $this->idleRuntimes)) {
            unset($this->idleRuntimes[$hash]);
        }

        if ($this->metrics instanceof Metrics) {
            $this->metrics->threads()->gauge(new Label('state', 'idle'))->dcr();
        }

        if (! array_key_exists($hash, $this->ttlTimers)) {
            return;
        }

        Loop::cancelTimer($this->ttlTimers[$hash]);

        unset($this->ttlTimers[$hash]);
    }
}
