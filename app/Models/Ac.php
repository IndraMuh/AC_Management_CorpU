<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ac extends Model
{
    protected $fillable = [
    'room_id',
    'ac_type', 
    'model_type', 
    'brand', 
    'model', 
    'indoor_sn', 
    'next_service_date',
    'outdoor_sn', 
    'specifications', 
    'last_maintenance', 
    'image_indoor', 
    'image_outdoor', 
    'status', 
    'x_position', 
    'y_position'
];

    // Relasi: AC berada di satu ruangan
public function room(): BelongsTo
{
    return $this->belongsTo(Room::class);
}

// TAMBAHKAN INI
public function schedules()
{
    return $this->belongsToMany(Schedule::class, 'ac_schedule')
                ->withPivot('status')
                ->withTimestamps();
}

    /**
     * Helper: Mendapatkan data Lantai dan Gedung secara berjenjang
     * Ini memudahkan Anda memanggil $ac->floor atau $ac->building
     */
    public function getFloorAttribute()
    {
        return $this->room->floor;
    }

    public function getBuildingAttribute()
    {
        return $this->room->floor->building;
    }

    
}