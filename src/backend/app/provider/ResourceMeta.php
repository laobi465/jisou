<?php
declare(strict_types=1);

namespace app\provider;

/**
 * 资源元数据（标准化产物）
 *
 * 各 Provider 抓取解析后统一映射为此结构，写入 resources 表。
 */
final class ResourceMeta
{
    /** @var string 资源标题 */
    public readonly string $title;

    /** @var string 来源标识（baidu / aliyun / telegram 等） */
    public readonly string $source;

    /** @var string 资源原始链接 */
    public readonly string $sourceUrl;

    /** @var string 链接规范化后的去重 hash（sha256） */
    public readonly string $urlHash;

    /** @var int|null 文件总大小（字节），未知为 null */
    public readonly ?int $sizeBytes;

    /** @var string|null 文件类型描述（如 "影视" / "文档"） */
    public readonly ?string $fileType;

    /** @var string[] 文件列表（文件名，详情页用） */
    public readonly array $files;

    /** @var string|null 抓取来源页 URL（如 TG 频道消息链接） */
    public readonly ?string $originUrl;

    /** @var string ISO8601 抓取时间 */
    public readonly string $extractedAt;

    public function __construct(
        string $title,
        string $source,
        string $sourceUrl,
        ?int $sizeBytes = null,
        ?string $fileType = null,
        array $files = [],
        ?string $originUrl = null
    ) {
        $title = trim($title);
        if ($title === '') {
            throw new \InvalidArgumentException('资源标题不能为空');
        }

        $this->title       = $title;
        $this->source      = $source;
        $this->sourceUrl   = $sourceUrl;
        $this->urlHash     = hash('sha256', self::normalizeUrl($sourceUrl));
        $this->sizeBytes   = $sizeBytes;
        $this->fileType    = $fileType;
        $this->files       = array_values($files);
        $this->originUrl   = $originUrl;
        $this->extractedAt = date('c');
    }

    /**
     * URL 规范化（用于去重 hash 计算）
     *
     * 去除 fragment、统一 scheme、去除尾部斜杠、小写化域名。
     */
    public static function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host   = strtolower($parsed['host'] ?? '');
        $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path   = $parsed['path'] ?? '';
        $query  = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        // 去除尾部斜杠（根路径除外）
        if ($path !== '/' && strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }
}
