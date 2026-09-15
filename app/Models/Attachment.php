<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    public function uploaduser()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
