<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'currency', 'owner_id'];

    /** 账本所有者：ledgers.owner_id → users.id（唯一能改/删账本本身的人） */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** 账本成员：ledger_members.ledger_id → ledgers.id（含 owner 自己那一行） */
    public function members()
    {
        return $this->hasMany(LedgerMember::class);
    }

    /** 流水：transactions.ledger_id → ledgers.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** 附件：attachments.ledger_id → ledgers.id */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    /** 某个用户是不是这个账本的成员（用于权限判断，单条查询） */
    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /** 某个用户在这个账本里的角色（不是成员则返回 null） */
    public function roleOf(int $userId): ?string
    {
        return $this->members()->where('user_id', $userId)->value('role');
    }
}
