<?php

//app/models/user.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'balance',
        'webhook_url',
        'webhook_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

        // Each user has many transactions
    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

        // Each user has many withdrawals
    public function withdrawal()
    {
        return $this->hasMany(Withdrawal::class);
    }

        // Each user has one bankDetail
    public function bankDetail()
    {
        return $this->hasOne(BankDetail::class);
    }

        // Each user has one setting
    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

}
