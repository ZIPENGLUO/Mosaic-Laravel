<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    /** 创建者：families.owner_id → users.id */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** 成员记录：family_members.family_id → families.id */
    public function members()
    {
        return $this->hasMany(FamilyMember::class);
    }

    /** 家庭下的账本：ledgers.family_id → families.id */
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
