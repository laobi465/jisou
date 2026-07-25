<?php
declare(strict_types=1);

namespace app\provider;

/**
 * 查询对象
 *
 * 由 Controller 构造，传入 AggregatorService 与各 Provider 的 search()。
 */
final class Query
{
    /** @var string 搜索关键词（已 trim） */
    public readonly string $keyword;

    /** @var string[] 启用的来源标识（如 ['baidu', 'aliyun']） */
    public readonly array $sources;

    /** @var int 页码，从 1 开始 */
    public readonly int $page;

    /** @var int 每页条数，1-50 */
    public readonly int $size;

    /** @var array<string,mixed> 扩展筛选（time_range / min_size / max_size / status） */
    public readonly array $filters;

    public function __construct(
        string $keyword,
        array $sources = [],
        int $page = 1,
        int $size = 20,
        array $filters = []
    ) {
        $keyword = trim($keyword);
        if ($keyword === '') {
            throw new \InvalidArgumentException('关键词不能为空');
        }
        if ($page < 1) {
            throw new \InvalidArgumentException('page 不能小于 1');
        }
        if ($size < 1 || $size > 50) {
            throw new \InvalidArgumentException('size 必须在 1-50 之间');
        }

        $this->keyword = $keyword;
        $this->sources = array_values($sources);
        $this->page    = $page;
        $this->size    = $size;
        $this->filters = $filters;
    }

    /**
     * 计算偏移量
     */
    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }

    /**
     * 生成缓存 key（按 keyword + sources + filters，不含分页以便缓存复用）
     */
    public function cacheKey(): string
    {
        $parts = [
            'kw:' . mb_strtolower($this->keyword),
            'src:' . implode(',', $this->sources),
            'f:' . json_encode($this->filters, JSON_UNESCAPED_UNICODE),
        ];
        return 'search:' . hash('sha256', implode('|', $parts));
    }
}
