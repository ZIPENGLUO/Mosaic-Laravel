<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    /** 账本所有者：ledgers.owner_id → users.id */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** 所属家庭：ledgers.family_id → families.id（个人账本为 null） */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /** 账户：accounts.ledger_id → ledgers.id */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /** 分类：categories.ledger_id → ledgers.id */
    public function categories()
    {
        return $this->hasMany(Category::class);
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
}
