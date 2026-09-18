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

    /** 我创建的家庭：families.owner_id → users.id */
    public function ownedFamilies()
    {
        return $this->hasMany(Family::class, 'owner_id');
    }

    /** 我的家庭身份记录：family_members.user_id → users.id */
    public function familyMemberships()
    {
        return $this->hasMany(FamilyMember::class);
    }

    /** 我拥有的账本：ledgers.owner_id → users.id */
    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'owner_id');
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

