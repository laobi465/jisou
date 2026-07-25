<?php
declare(strict_types=1);

namespace app\middleware;

use app\common\ApiResponse;
use app\common\ErrorCode;
use think\Request;
use think\Response;

/**
 * 超管鉴权中间件
 *
 * 校验 JWT + 角色为 admin。
 * 待接入：M3 里程碑实现 JWT 签发与角色校验。
 */
final class AdminAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        // 待接入：M3 里程碑实现超管 JWT 校验
        // 当前为骨架，显式失败防止误用
        return json(
            ApiResponse::error(ErrorCode::PERMISSION_DENIED, '待接入：超管鉴权未启用')->jsonSerialize()
        )->code(403)->header(['Content-Type' => 'application/json; charset=utf-8']);
    }
}
