<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
| 收支分类：属于账本，所以嵌套在账本下（和"账户属于个人"相反）
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/ledgers/{id}/categories', [CategoryController::class, 'index']);
    Route::post('/ledgers/{id}/categories', [CategoryController::class, 'store']);
    Route::put('/ledgers/{id}/categories/{categoryId}', [CategoryController::class, 'update']);
    Route::delete('/ledgers/{id}/categories/{categoryId}', [CategoryController::class, 'destroy']);
});
