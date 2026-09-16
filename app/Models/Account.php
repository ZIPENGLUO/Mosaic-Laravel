<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /** 所属账本：accounts.ledger_id → ledgers.id */
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    /** 该账户下的流水：transactions.account_id → accounts.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
