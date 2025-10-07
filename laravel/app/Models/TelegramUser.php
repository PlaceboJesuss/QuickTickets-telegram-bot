<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramUser extends Model
{
    protected $primaryKey = 'chat_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'chat_id',
        'username',
    ];

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'user_places', 'chat_id', 'place_id');
    }
}
