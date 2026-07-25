<?php
declare(strict_types=1);

namespace app\provider;

/**
 * 解析得到的真实下载链接
 *
 * 仅当 Provider 声明 CAN_PARSE 且解析成功时返回。
 * 下载链接通常有时效，调用方需记录 expiresAt。
 */
final class DownloadUrl
{
    /** @var string 真实下载链接 */
    public readonly string $url;

    /** @var string|null 链接过期时间（ISO8601），无过期则为 null */
    public readonly ?string $expiresAt;

    /** @var string|null 关联文件名 */
    public readonly ?string $filename;

    /** @var int|null 文件大小（字节） */
    public readonly ?int $sizeBytes;

    public function __construct(
        string $url,
        ?string $expiresAt = null,
        ?string $filename = null,
        ?int $sizeBytes = null
    ) {
        if (trim($url) === '') {
            throw new \InvalidArgumentException('下载链接不能为空');
        }
        $this->url       = $url;
        $this->expiresAt = $expiresAt;
        $this->filename  = $filename;
        $this->sizeBytes = $sizeBytes;
    }
}
