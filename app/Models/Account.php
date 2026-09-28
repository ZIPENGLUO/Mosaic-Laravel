<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /** 允许批量赋值的字段（user_id 由后端填，绝不接受客户端传入） */
    protected $fillable = ['user_id', 'name', 'type', 'opening_balance'];

    /** 所属用户：accounts.user_id → users.id（账户属于个人，不属于账本） */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 该账户下的流水：transactions.account_id → accounts.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
