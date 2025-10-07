<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = ['name', 'url'];

    public function users()
    {
        return $this->belongsToMany(TelegramUser::class, 'place_user', 'place_id', 'chat_id');
    }

    public function performances()
    {
        return $this->hasMany(Performance::class);
    }
}
