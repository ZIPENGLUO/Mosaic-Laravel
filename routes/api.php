<?php

use Illuminate\Support\Facades\Route;

/*
| API 路由总入口：按模块拆分，具体见 routes/api/ 目录
*/

require __DIR__.'/api/auth.php';
// require __DIR__.'/api/ledger.php';      // 以后写好再加