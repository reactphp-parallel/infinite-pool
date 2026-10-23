<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Infinite;

use Random\RandomException;
use ReactParallel\Contracts\GroupInterface;

use function bin2hex;
use function random_bytes;

final readonly class Group implements GroupInterface
{
    private const int BYTES = 32;

    private function __construct(private string $id)
    {
    }

    /**
     * @param int<1, max>|null $bytes
     *
     * @throws RandomException
     */
    public static function create(int|null $bytes = self::BYTES): self
    {
        return new self(bin2hex(random_bytes($bytes ?? self::BYTES)));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
