<?php
use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/ledgers', [LedgerController::class, 'index']);
    Route::get('/ledgers/{id}', [LedgerController::class, 'show']);

});