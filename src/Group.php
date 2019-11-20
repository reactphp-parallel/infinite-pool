<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use function bin2hex;
use function random_bytes;

final class Group implements GroupInterface
{
    private const BYTES = 16;

    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function create(): self
    {
        return new self(bin2hex(random_bytes(self::BYTES)));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
