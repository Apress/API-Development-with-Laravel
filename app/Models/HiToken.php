<?php

//app/Models/HiToken.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        's_token',
        'p_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            's_token' => 'encrypted',
            'p_token' => 'encrypted',
        ];
    }
}
