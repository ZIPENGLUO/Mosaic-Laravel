<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 分类删除策略（决策 B，与账户保持一致）：
 * 被流水引用的分类，用户二次确认后可以删除；删除后关联流水的 category_id 变为 NULL
 * （前端显示为"未分类"），而不是把记账历史一起删掉、也不是被外键拦住。
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function ($table) {
            $table->dropForeign(['category_id']);
        });
        DB::statement('ALTER TABLE transactions MODIFY category_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
    }

    public function down()
    {
        Schema::table('transactions', function ($table) {
            $table->dropForeign(['category_id']);
        });
        // 回滚前先把 NULL 的分类清掉占位：这里没有安全的兜底值，直接删除这些流水不可接受，
        // 因此回滚要求"没有 NULL 的 category_id"，否则 NOT NULL 约束会失败并中止回滚。
        DB::statement('ALTER TABLE transactions MODIFY category_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT');
    }
};
