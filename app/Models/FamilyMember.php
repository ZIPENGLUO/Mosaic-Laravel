<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    /** 所属家庭：family_members.family_id → families.id */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /** 对应账号：family_members.user_id → users.id */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
