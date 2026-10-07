<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /** 我拥有（创建）的账本：ledgers.owner_id → users.id */
    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'owner_id');
    }

    /**
     * 我的账本成员身份记录：ledger_members.user_id → users.id
     * （一个用户可以在多个账本里有不同角色）
     */
    public function ledgerMemberships()
    {
        return $this->hasMany(LedgerMember::class);
    }

    /** 我参与的全部账本（作为成员；含自己拥有的） */
    public function memberLedgers()
    {
        return $this->belongsToMany(Ledger::class, 'ledger_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** 我的支付账户：accounts.user_id → users.id（属于个人设置，跨账本复用） */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /** 我记录的流水：transactions.created_by → users.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /** 我上传的附件：attachments.uploaded_by → users.id */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }
}

