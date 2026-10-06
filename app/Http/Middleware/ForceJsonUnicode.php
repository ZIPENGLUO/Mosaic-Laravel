<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 让 JSON 响应直接输出中文（而不是 \u6797\u6c0f 这样的 Unicode 转义）。
 *
 * 背景：本项目 Laravel 9.52 装的是 symfony/http-foundation 6.0，
 * 该版本 JsonResponse 的默认 encodingOptions 是 0（不含 UNESCAPED 标志），
 * 所以 response()->json() 会把中文转义。数据本身没错，但
 * Postman 的 Raw 视图、日志里看响应非常难读，排查问题时容易看漏。
 *
 * 为什么用中间件而不是别的方式（都实测过）：
 *   ✗ JsonResponse::setEncodingOptions()  —— 是实例方法，静态调用直接报错
 *   ✗ Response::macro('json', ...)        —— Laravel 9 的 response()->json() 不走这个宏，无效
 *   ✓ 中间件在响应生成后、发回浏览器前调用实例方法 —— 一定生效
 */
class ForceJsonUnicode
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        return $response;
    }
}
