<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /** 允许批量赋值的字段（ledger_id 由后端填，不接受客户端传入） */
    protected $fillable = ['ledger_id', 'name', 'type', 'icon', 'sort_order'];

    /** 所属账本：categories.ledger_id → ledgers.id（分类属于账本，和账户相反） */
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
