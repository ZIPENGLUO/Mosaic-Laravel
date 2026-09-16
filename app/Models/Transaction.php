<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    // SoftDeletes：删除时只打 deleted_at 标记，查询自动排除，数据不真丢
    use HasFactory, SoftDeletes;

    /** 所属账本：transactions.ledger_id → ledgers.id */
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    /** 所属账户：transactions.account_id → accounts.id */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /** 所属分类：transactions.category_id → categories.id */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** 记账人：transactions.created_by → users.id */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** 附件：attachments.transaction_id → transactions.id */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
