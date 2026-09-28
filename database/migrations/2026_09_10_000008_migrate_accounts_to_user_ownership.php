<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 账户归属调整：账户是"我用什么付款"，属于个人（设置里的东西），不属于账本。
 *
 * 1. accounts：新增 user_id → users.id，删除 ledger_id → ledgers.id，唯一约束改为 (user_id, name)
 * 2. transactions.account_id：改为可空 + nullOnDelete ——
 *    这样删掉一个账户时，历史流水的 account_id 变成 NULL（付款方式已删除），
 *    而不是被外键拦住、也不是把记账历史一起删掉。
 */
return new class extends Migration
{
    public function up()
    {
        // ---------- 1. accounts 改为属于用户 ----------
        Schema::table('accounts', function ($table) {
            // ⚠️ 顺序要紧：MySQL 的外键需要索引支撑，
            //    所以必须"先删外键、再删索引"（反过来会报 errno 1553）
            $table->dropForeign('accounts_ledger_id_foreign');
            $table->dropUnique('accounts_ledger_id_name_unique');
        });

        // 用原生 SQL 加列，避免依赖 doctrine/dbal（本项目没装）
        DB::statement('ALTER TABLE accounts ADD user_id BIGINT UNSIGNED NULL AFTER id');
        DB::statement(
            'UPDATE accounts a
             JOIN ledgers l ON l.id = a.ledger_id
             SET a.user_id = l.owner_id'
        );
        DB::statement('ALTER TABLE accounts MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE accounts DROP COLUMN ledger_id');
        DB::statement('ALTER TABLE accounts ADD UNIQUE accounts_user_id_name_unique (user_id, name)');

        // ---------- 2. transactions.account_id 改为可空 + 删账户时置空 ----------
        Schema::table('transactions', function ($table) {
            $table->dropForeign(['account_id']);
        });
        DB::statement('ALTER TABLE transactions MODIFY account_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_account_id_foreign FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE SET NULL');
    }

    public function down()
    {
        // ---------- 回滚 transactions ----------
        Schema::table('transactions', function ($table) {
            $table->dropForeign(['account_id']);
        });
        DB::statement('ALTER TABLE transactions MODIFY account_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_account_id_foreign FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE RESTRICT');

        // ---------- 回滚 accounts ----------
        Schema::table('accounts', function ($table) {
            // 同样先删外键再删索引
            $table->dropForeign(['user_id']);
            $table->dropUnique('accounts_user_id_name_unique');
        });
        DB::statement('ALTER TABLE accounts ADD ledger_id BIGINT UNSIGNED NULL AFTER id');
        // 回到"账户属于账本"：尽量挂回该用户拥有的第一个账本
        DB::statement(
            'UPDATE accounts a
             JOIN (SELECT owner_id, MIN(id) AS ledger_id FROM ledgers GROUP BY owner_id) l
               ON l.owner_id = a.user_id
             SET a.ledger_id = l.ledger_id'
        );
        DB::statement('ALTER TABLE accounts MODIFY ledger_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT accounts_ledger_id_foreign FOREIGN KEY (ledger_id) REFERENCES ledgers (id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE accounts DROP COLUMN user_id');
        DB::statement('ALTER TABLE accounts ADD UNIQUE accounts_ledger_id_name_unique (ledger_id, name)');
    }
};
