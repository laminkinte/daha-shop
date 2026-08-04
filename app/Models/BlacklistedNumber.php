<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedNumber extends Model
{
    protected $fillable = ['phone', 'email', 'reason', 'blocked_at'];

    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
        ];
    }
}
