<?php
declare(strict_types=1);

namespace app\provider;

/**
 * Provider 健康状态
 *
 * 用于超管后台展示与 AggregatorService 熔断判断。
 */
final class HealthStatus
{
    public const HEALTHY   = 'healthy';
    public const DEGRADED  = 'degraded';
    public const DOWN      = 'down';

    public readonly string $status;

    /** @var int|null 最近 1 分钟调用量 */
    public readonly ?int $recentCalls;

    /** @var float|null 最近 1 分钟错误率（0-1） */
    public readonly ?float $errorRate;

    /** @var int|null 平均响应时间（毫秒） */
    public readonly ?int $avgLatencyMs;

    /** @var string|null 状态详情（如异常原因） */
    public readonly ?string $message;

    /** @var string ISO8601 检查时间 */
    public readonly string $checkedAt;

    public function __construct(
        string $status,
        ?int $recentCalls = null,
        ?float $errorRate = null,
        ?int $avgLatencyMs = null,
        ?string $message = null
    ) {
        if (!in_array($status, [self::HEALTHY, self::DEGRADED, self::DOWN], true)) {
            throw new \InvalidArgumentException("未知健康状态: {$status}");
        }
        $this->status       = $status;
        $this->recentCalls  = $recentCalls;
        $this->errorRate    = $errorRate;
        $this->avgLatencyMs = $avgLatencyMs;
        $this->message      = $message;
        $this->checkedAt    = date('c');
    }

    public static function healthy(?string $message = null): self
    {
        return new self(self::HEALTHY, message: $message);
    }

    public static function degraded(string $message): self
    {
        return new self(self::DEGRADED, message: $message);
    }

    public static function down(string $message): self
    {
        return new self(self::DOWN, message: $message);
    }

    public function isAvailable(): bool
    {
        return $this->status !== self::DOWN;
    }
}
