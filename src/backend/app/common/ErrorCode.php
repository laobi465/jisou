<?php
declare(strict_types=1);

namespace app\common;

/**
 * 错误码枚举
 *
 * 所有 API 响应错误码必须沿用此枚举，禁止自创状态码。
 * 详见 SPEC.md §2.3.3
 */
final class ErrorCode
{
    // ===== 成功 =====
    public const OK = 0;

    // ===== 1000-1999 鉴权类 =====
    public const UNAUTHENTICATED      = 1001; // 未登录
    public const TOKEN_INVALID        = 1002; // token 失效
    public const PERMISSION_DENIED    = 1003; // 权限不足

    // ===== 2000-2999 参数类 =====
    public const PARAM_MISSING        = 2001; // 参数缺失
    public const PARAM_FORMAT_INVALID = 2002; // 参数格式错误
    public const PARAM_OUT_OF_RANGE   = 2003; // 参数越界

    // ===== 3000-3999 资源类 =====
    public const RESOURCE_NOT_FOUND   = 3001; // 资源不存在
    public const RESOURCE_INVALID     = 3002; // 资源已失效
    public const RESOURCE_FAVORITED   = 3003; // 资源已收藏

    // ===== 4000-4999 业务类 =====
    public const QUERY_TOO_SHORT      = 4001; // 搜索词过短
    public const SOURCE_NOT_ENABLED   = 4002; // 来源未启用
    public const PARSE_FAILED         = 4003; // 解析失败
    public const PARSE_MODULE_OFF     = 4004; // 解析模块已关闭

    // ===== 5000-5999 Provider 类 =====
    public const PROVIDER_UNHEALTHY   = 5001; // Provider 不健康
    public const PROVIDER_TIMEOUT     = 5002; // Provider 超时
    public const PROVIDER_RATE_LIMITED = 5003; // Provider 限流

    // ===== 6000-6999 爬虫类 =====
    public const CRAWL_TASK_NOT_FOUND = 6001; // 任务不存在
    public const CRAWL_TASK_PAUSED    = 6002; // 任务已暂停
    public const CRAWL_FAILED         = 6003; // 抓取失败

    // ===== 9000-9999 系统类 =====
    public const DB_ERROR             = 9001; // 数据库错误
    public const CACHE_ERROR          = 9002; // 缓存错误
    public const SEARCH_ENGINE_ERROR  = 9003; // 搜索引擎错误
    public const UNKNOWN_ERROR        = 9999; // 未知错误

    /**
     * 错误码 → 默认消息映射
     *
     * @return array<int,string>
     */
    public static function messages(): array
    {
        return [
            self::OK                     => 'ok',
            self::UNAUTHENTICATED        => '未登录',
            self::TOKEN_INVALID          => 'token 失效，请重新登录',
            self::PERMISSION_DENIED      => '权限不足',
            self::PARAM_MISSING          => '参数缺失',
            self::PARAM_FORMAT_INVALID   => '参数格式错误',
            self::PARAM_OUT_OF_RANGE     => '参数越界',
            self::RESOURCE_NOT_FOUND     => '资源不存在',
            self::RESOURCE_INVALID       => '资源已失效',
            self::RESOURCE_FAVORITED     => '资源已收藏',
            self::QUERY_TOO_SHORT        => '搜索词过短',
            self::SOURCE_NOT_ENABLED     => '来源未启用',
            self::PARSE_FAILED           => '解析失败',
            self::PARSE_MODULE_OFF       => '解析模块已关闭',
            self::PROVIDER_UNHEALTHY     => '数据源不健康',
            self::PROVIDER_TIMEOUT       => '数据源超时',
            self::PROVIDER_RATE_LIMITED  => '数据源限流',
            self::CRAWL_TASK_NOT_FOUND   => '爬虫任务不存在',
            self::CRAWL_TASK_PAUSED      => '爬虫任务已暂停',
            self::CRAWL_FAILED           => '抓取失败',
            self::DB_ERROR               => '数据库错误',
            self::CACHE_ERROR            => '缓存错误',
            self::SEARCH_ENGINE_ERROR    => '搜索引擎错误',
            self::UNKNOWN_ERROR          => '未知错误',
        ];
    }

    public static function message(int $code): string
    {
        return self::messages()[$code] ?? '未知错误';
    }
}
