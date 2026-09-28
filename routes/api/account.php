<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
| 支付账户：属于个人（设置），不嵌套在账本下
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
});
