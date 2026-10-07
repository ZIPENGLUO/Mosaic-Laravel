<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/ledgers/{id}/transactions', [TransactionController::class, 'index']);
    Route::post('/ledgers/{id}/transactions', [TransactionController::class, 'store']);
    Route::get('/ledgers/{id}/transactions/{txId}', [TransactionController::class, 'show']);
    Route::put('/ledgers/{id}/transactions/{txId}', [TransactionController::class, 'update']);
    Route::delete('/ledgers/{id}/transactions/{txId}', [TransactionController::class, 'destroy']);
    // 批量删除放在最后，路径不冲突
    Route::post('/ledgers/{id}/transactions/batch-delete', [TransactionController::class, 'batchDestroy']);
});
