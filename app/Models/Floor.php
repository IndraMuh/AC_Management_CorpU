<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity; // Tambahkan ini
use Spatie\Activitylog\LogOptions; // Tambahkan ini

class Floor extends Model
{
    use LogsActivity;    
protected $fillable = ['building_id', 'name', 'floor_plan', 'rotation'];

    // Relasi: Lantai milik sebuah gedung
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function getActivitylogOptions(): LogOptions // Tambahkan fungsi ini
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relasi: Satu lantai memiliki banyak ruangan
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}