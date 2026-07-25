<?php
declare(strict_types=1);

namespace app\middleware;

use app\common\ApiResponse;
use app\common\ErrorCode;
use think\Request;
use think\Response;

/**
 * 用户鉴权中间件
 *
 * 校验 JWT access token。
 * 待接入：M3 里程碑实现 JWT 签发与校验逻辑。
 */
final class Auth
{
    public function handle(Request $request, \Closure $next): Response
    {
        // 待接入：M3 里程碑实现 JWT 校验
        // 当前为骨架，显式失败防止误用
        throw new \RuntimeException('待接入：M3 里程碑实现 JWT 鉴权');
    }
}
