<?php

use App\Http\Controllers\FamilyMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/families/{familyId}/members', [FamilyMemberController::class, 'index']);
});
