<?php

//app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'fee',
        'status',
        'transaction_ref',
        'email',
        'currency',
        'redirect_url',
        'pass_charge',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'user_id',
        'checkout_url',
    ];

    // Each transaction belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each transaction has one payment
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
