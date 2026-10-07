<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 账本成员（**每个账本自己的成员与角色**）
 *
 * 取代了旧的家庭分组模型（families + family_members）：
 *   - 一个账本可以有多个成员（家人、室友、旅行同伴…）
 *   - 同一个用户可以在不同账本里有不同角色
 *   - 账本所有者（ledgers.owner_id）必定在成员表里有一行 role = owner
 *
 * 角色语义：
 *   owner  —— 账本所有者（唯一能改/删账本本身的人）
 *   admin  —— 管理员（可改/删账本内任何人的流水）
 *   member —— 普通成员（只能改/删自己记的流水）
 */
class LedgerMember extends Model
{
    use HasFactory;

    /** ledger_id 由关联创建时自动写入，不接受客户端传入 */
    protected $fillable = ['user_id', 'role'];

    /** 所属账本：ledger_members.ledger_id → ledgers.id */
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    /** 对应的用户：ledger_members.user_id → users.id */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 是否管理角色（所有者或管理员） */
    public function isManager(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
