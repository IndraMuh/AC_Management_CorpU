<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity; // Tambahkan ini
use Spatie\Activitylog\LogOptions; // Tambahkan ini

class Building extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'image'];

    // Relasi: Satu gedung memiliki banyak lantai
public function floors(): HasMany
{
    return $this->hasMany(Floor::class);
}

public function getActivitylogOptions(): LogOptions // Tambahkan fungsi ini
    {
        return LogOptions::defaults()
            ->logAll() // Mencatat semua kolom di $fillable
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Accessor untuk mengambil semua AC di gedung ini.
     * Digunakan agar $building->acs tidak null.
     */
    public function getAcsAttribute()
    {
        // Mengumpulkan semua AC dari setiap ruangan di setiap lantai
        return Ac::whereHas('room.floor', function ($query) {
            $query->where('building_id', $this->id);
        })->get();
    }
}