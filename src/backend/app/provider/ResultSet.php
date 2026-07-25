<?php
declare(strict_types=1);

namespace app\provider;

/**
 * 搜索结果集
 */
final class ResultSet
{
    /** @var ResourceMeta[] 结果项 */
    public readonly array $items;

    /** @var int 总数（可能为估算值，来源不支持精确总数时为 0） */
    public readonly int $total;

    /** @var int 本次查询耗时（毫秒） */
    public readonly int $tookMs;

    /** @var string 来源标识 */
    public readonly string $source;

    /** @var bool 是否来自缓存 */
    public readonly bool $fromCache;

    /**
     * @param ResourceMeta[] $items
     */
    public function __construct(
        array $items,
        int $total,
        int $tookMs,
        string $source,
        bool $fromCache = false
    ) {
        $this->items    = array_values($items);
        $this->total    = $total;
        $this->tookMs   = $tookMs;
        $this->source   = $source;
        $this->fromCache = $fromCache;
    }

    public static function empty(string $source, int $tookMs = 0): self
    {
        return new self([], 0, $tookMs, $source);
    }
}
