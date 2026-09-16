<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    /** ocr_result 是 json 列，转成 PHP 数组用 */
    protected $casts = [
        'ocr_result' => 'array',
    ];

    /** 所属账本：attachments.ledger_id → ledgers.id */
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    /** 关联流水：attachments.transaction_id → transactions.id（可空） */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /** 上传人：attachments.uploaded_by → users.id */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
