<?php
declare(strict_types=1);

namespace app\common;

use JsonSerializable;

/**
 * 统一响应体
 *
 * 详见 SPEC.md §2.3.2
 *
 * {
 *   "code": 0,
 *   "message": "ok",
 *   "data": { ... }
 * }
 */
final class ApiResponse implements JsonSerializable
{
    public readonly int $code;
    public readonly string $message;
    public readonly mixed $data;

    public function __construct(int $code, string $message, mixed $data = null)
    {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    /**
     * 成功响应
     */
    public static function success(mixed $data = null, string $message = 'ok'): self
    {
        return new self(ErrorCode::OK, $message, $data);
    }

    /**
     * 失败响应（自动填充默认消息）
     */
    public static function error(int $code, ?string $message = null, mixed $data = null): self
    {
        return new self(
            $code,
            $message ?? ErrorCode::message($code),
            $data
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
