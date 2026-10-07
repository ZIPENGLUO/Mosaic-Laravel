<?php

use Illuminate\Support\Facades\Route;

/*
| API 路由总入口：按模块拆分，具体见 routes/api/ 目录
*/

require __DIR__.'/api/auth.php';
require __DIR__ . '/api/ledger.php';
require __DIR__ . '/api/ledgermember.php';
require __DIR__ . '/api/account.php';
require __DIR__ . '/api/category.php';
require __DIR__ . '/api/transaction.php';
