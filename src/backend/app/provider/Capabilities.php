<?php
declare(strict_types=1);

namespace app\provider;

/**
 * Provider 能力声明
 *
 * 用位标记组合，便于 AggregatorService 快速判断。
 */
final class Capabilities
{
    public const CAN_SEARCH = 0b001;
    public const CAN_FETCH  = 0b010;
    public const CAN_PARSE  = 0b100;

    private int $flags;

    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }

    public static function none(): self
    {
        return new self(0);
    }

    public static function searchOnly(): self
    {
        return new self(self::CAN_SEARCH);
    }

    public static function searchAndFetch(): self
    {
        return new self(self::CAN_SEARCH | self::CAN_FETCH);
    }

    public function withParse(): self
    {
        return new self($this->flags | self::CAN_PARSE);
    }

    public function canSearch(): bool
    {
        return ($this->flags & self::CAN_SEARCH) !== 0;
    }

    public function canFetch(): bool
    {
        return ($this->flags & self::CAN_FETCH) !== 0;
    }

    public function canParse(): bool
    {
        return ($this->flags & self::CAN_PARSE) !== 0;
    }

    public function toInt(): int
    {
        return $this->flags;
    }
}
