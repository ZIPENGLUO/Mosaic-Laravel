<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * 按请求决定语言（多语言基础）
 *
 * 优先级：已登录用户的偏好 → Accept-Language 请求头 → 默认 APP_LOCALE
 *
 * 前端只需在请求头带上 Accept-Language（如 zh-CN / en / ja），
 * 后端的校验消息、分页文案等都会自动切换语言。
 * 以后新增语言：lang/ 下加目录 + config/app.php 的 supported_locales 加一项。
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = config('app.supported_locales', ['zh_CN', 'en']);

        // 1. 已登录用户的语言偏好（users.locale 字段，可选；没这个字段就跳过）
        $locale = null;
        if ($user = $request->user()) {
            $locale = $this->normalize($user->locale ?? null, $supported);
        }

        // 2. 请求头 Accept-Language（getPreferredLanguage 会处理 q=0.9 之类的权重）
        if (! $locale) {
            $preferred = $request->getPreferredLanguage($supported);
            $locale = $this->normalize($preferred, $supported);
        }

        // 3. 设置（找不到就用 config('app.locale')）
        if ($locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * 归一化语言标识：zh-CN / zh-cn / zh → zh_CN
     */
    private function normalize(?string $locale, array $supported): ?string
    {
        if (! $locale) {
            return null;
        }

        $value = str_replace('-', '_', $locale);
        $lower = strtolower($value);

        foreach ($supported as $item) {
            $itemLower = strtolower($item);
            if ($itemLower === $lower) {
                return $item;
            }
            // zh_cn / zh_hans_cn 之类的前缀匹配（zh 也能命中 zh_CN）
            if (str_starts_with($lower, $itemLower) || str_starts_with($itemLower, $lower)) {
                return $item;
            }
        }

        return null;
    }
}
