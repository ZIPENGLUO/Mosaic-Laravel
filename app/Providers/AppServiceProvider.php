<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // JSON 中文不转义由 app/Http/Middleware/ForceJsonUnicode.php 处理
        // （这里曾尝试用 Response::macro('json') 覆盖，实测在 Laravel 9 上不生效）
    }
}
