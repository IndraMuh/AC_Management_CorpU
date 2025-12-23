<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    protected $fillable = ['building_id', 'name', 'floor_plan'];

    // Relasi: Lantai milik sebuah gedung
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    // Relasi: Satu lantai memiliki banyak ruangan
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}