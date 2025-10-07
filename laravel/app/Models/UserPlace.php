<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPlace extends Model
{
    protected $fillable = [
        'chat_id',
        'place_id',
    ];

    public $timestamps = false; // если pivot не хранит created_at/updated_at
}
