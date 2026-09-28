<?php

use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/ledgers', [LedgerController::class, 'index']);
    Route::get('/ledgers/{id}', [LedgerController::class, 'show']);
    Route::post('/ledgers', [LedgerController::class, 'store']);
    Route::put('/ledgers/{id}', [LedgerController::class, 'update']);
    Route::delete('/ledgers/{id}', [LedgerController::class, 'destroy']);
});
