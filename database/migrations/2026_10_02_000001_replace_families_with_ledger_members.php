<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 成员模型重构：**删除"家庭分组"概念，改为每个账本自带成员**
 *
 * 之前：families + family_members（一套分组级成员）+ ledgers.family_id
 *        → "账本谁能看"要绕到分组去判断，分组和账本概念重合
 * 之后：ledger_members（每个账本自己的成员 + 角色）
 *        → 只有"账本"一个概念，成员是账本的属性
 *        → 合租、旅行团、项目组都能用，不再绑定"家庭"
 *
 * 数据搬迁：把每个账本原来所属分组（family）的成员，复制成该账本的成员。
 *
 * ⚠️ down() 不可逆（旧的分组归属无法还原），需要回滚请恢复数据库备份。
 */
return new class extends Migration
{
    public function up()
    {
        // 1) 建账本成员表
        Schema::create('ledger_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->timestamps();
            $table->unique(['ledger_id', 'user_id']);
        });

        // 2) 数据搬迁：原本"挂家庭"的账本，把家庭会员搬成账本会员
        //    只搬确实存在的家庭成员（historical 数据可能不一致，legacy 这里没有天然回滚）
        DB::statement('
            INSERT INTO ledger_members (ledger_id, user_id, role, created_at, updated_at)
            SELECT DISTINCT l.id, fm.user_id,
                   CASE
                       WHEN fm.user_id = l.owner_id THEN "owner"
                       WHEN fm.role = "owner" THEN "admin"
                       ELSE fm.role
                   END,
                   NOW(), NOW()
            FROM ledgers l
            JOIN family_members fm ON fm.family_id = l.family_id
            WHERE l.family_id IS NOT NULL
        ');

        // 3) 保证每个账本的 owner 都是成员（即使没挂分组 / 分组里没有他）
        DB::statement('
            INSERT INTO ledger_members (ledger_id, user_id, role, created_at, updated_at)
            SELECT l.id, l.owner_id, "owner", NOW(), NOW()
            FROM ledgers l
            WHERE NOT EXISTS (
                SELECT 1 FROM ledger_members lm
                WHERE lm.ledger_id = l.id AND lm.user_id = l.owner_id
            )
        ');

        // 4) 删掉账本上的分组字段
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropColumn('family_id');
        });

        // 5) 删掉分组概念的两张表
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('families');
    }

    public function down()
    {
        throw new RuntimeException('成员模型已改为账本级，旧的家庭分组归属无法还原；请恢复数据库备份。');
    }
};
