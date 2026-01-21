<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'iban',
        'swift',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }    //
}
