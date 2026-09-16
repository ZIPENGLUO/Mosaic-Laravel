<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /** 所属账本：categories.ledger_id → ledgers.id */
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    /** 该分类下的流水：transactions.category_id → categories.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
