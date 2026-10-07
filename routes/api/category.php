<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
| 收支分类：**全局公共目录**（不属于账本、不属于用户），定死，只读
|
| 旧设计是 /api/ledgers/{id}/categories 下的一整套 CRUD，
| 现已改为全局两级目录，不再需要按账本区分，也不再允许用户增删改。
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
});
