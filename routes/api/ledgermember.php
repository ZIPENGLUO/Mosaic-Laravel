<?php

use App\Http\Controllers\LedgerMemberController;
use Illuminate\Support\Facades\Route;

/*
| 账本成员：成员挂在**账本**上（一个账本一套成员）
|
| 旧路由 /api/families/{familyId}/members 已废弃（家庭分组概念已删除）
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/ledgers/{id}/members', [LedgerMemberController::class, 'index']);
});
