<?php
declare(strict_types=1);

namespace app\provider;

use RuntimeException;

/**
 * Provider 异常
 *
 * 用于 AggregatorService 区分 Provider 失败与系统错误，
 * 单个 Provider 抛此异常时其他 Provider 仍可继续。
 */
class ProviderException extends RuntimeException
{
    public const CODE_UNHEALTHY = 5001;
    public const CODE_TIMEOUT   = 5002;
    public const CODE_RATE_LIMITED = 5003;
    public const CODE_FETCH_FAILED = 5004;
    public const CODE_PARSE_FAILED = 5005;

    private string $providerName;

    public function __construct(
        string $providerName,
        string $message,
        int $code = self::CODE_FETCH_FAILED,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        parent::__construct("[{$providerName}] {$message}", $code, $previous);
    }

    public function providerName(): string
    {
        return $this->providerName;
    }

    public static function unhealthy(string $providerName, string $message = 'Provider 不健康'): self
    {
        return new self($providerName, $message, self::CODE_UNHEALTHY);
    }

    public static function timeout(string $providerName, int $timeoutMs): self
    {
        return new self(
            $providerName,
            "Provider 超时（{$timeoutMs}ms）",
            self::CODE_TIMEOUT
        );
    }

    public static function rateLimited(string $providerName): self
    {
        return new self($providerName, 'Provider 触发限流', self::CODE_RATE_LIMITED);
    }
}
