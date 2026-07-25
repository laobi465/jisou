<?php
declare(strict_types=1);

use think\facade\Route;

// ===== 健康检查 =====
Route::get('health', function () {
    return json(['code' => 0, 'message' => 'ok', 'data' => ['service' => 'jisou', 'status' => 'up']]);
});

// ===== 搜索 API（前缀 /api）=====
Route::group('api', function () {
    // 聚合搜索
    Route::get('search', 'app\controller\api\Search@search');

    // 资源详情与解析
    Route::get('resource/:hash', 'app\controller\api\Resource@detail');
    Route::post('resource/:hash/parse', 'app\controller\api\Resource@parse');

    // 鉴权
    Route::post('auth/register', 'app\controller\api\Auth@register');
    Route::post('auth/login', 'app\controller\api\Auth@login');
    Route::post('auth/refresh', 'app\controller\api\Auth@refresh');

    // 用户中心（需鉴权中间件，M3 里程碑接入）
    Route::group(function () {
        Route::get('favorites', 'app\controller\api\Favorite@list');
        Route::post('favorites', 'app\controller\api\Favorite@add');
        Route::delete('favorites/:id', 'app\controller\api\Favorite@remove');

        Route::get('history', 'app\controller\api\History@list');
        Route::delete('history', 'app\controller\api\History@clear');

        Route::post('reports', 'app\controller\api\Report@submit');
    })->middleware(\app\middleware\Auth::class);

    // ===== 超管后台（前缀 /api/admin）=====
    Route::group('admin', function () {
        Route::get('dashboard', 'app\controller\admin\Dashboard@index');

        Route::get('providers', 'app\controller\admin\Provider@list');
        Route::put('providers/:id', 'app\controller\admin\Provider@update');
        Route::post('providers/:id/test', 'app\controller\admin\Provider@test');

        Route::get('crawl', 'app\controller\admin\Crawl@list');
        Route::post('crawl', 'app\controller\admin\Crawl@create');
        Route::post('crawl/:id/run', 'app\controller\admin\Crawl@run');

        Route::get('resources', 'app\controller\admin\Resource@list');
        Route::put('resources/:id', 'app\controller\admin\Resource@update');

        Route::get('blacklist', 'app\controller\admin\Blacklist@list');
        Route::post('blacklist', 'app\controller\admin\Blacklist@add');

        Route::get('users', 'app\controller\admin\User@list');
    })->middleware(\app\middleware\AdminAuth::class);
});
