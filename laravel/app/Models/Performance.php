<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    /**
     * Найти Performance по имени и timestamp.
     *
     * @param string $name
     * @param int $timestamp
     * @return Performance|null
     */
    public static function findPerformance(Place $place, string $name, int $timestamp): ?self
    {
        $datetime = date('Y-m-d H:i:s', $timestamp);
        return self::where('name', $name)
            ->where('time', $datetime)
            ->where('place_id', $place->id)
            ->first();
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}
