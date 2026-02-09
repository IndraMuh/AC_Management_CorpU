<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ac extends Model
{
    use LogsActivity;
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

// Tambahkan ini agar tanggal otomatis menjadi objek Carbon
protected $casts = [
    'last_maintenance' => 'date',
    'next_service_date' => 'date',
];

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        // Mengganti logOnly menjadi logAll untuk mencatat semua perubahan kolom di tabel acs
        ->logAll() 
        ->logOnlyDirty() // Tetap hanya mencatat jika ada perubahan data yang nyata
        ->dontSubmitEmptyLogs(); // Tetap jangan simpan log jika tidak ada perubahan
}

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
    
// File: app/Models/Ac.php

public function getNeedsServiceAttribute()
{
    // Cari jadwal terakhir yang statusnya 'selesai' DAN mengandung nama 'service'
    $lastService = $this->schedules()
        ->wherePivot('status', 'selesai')
        ->where('name', 'like', '%service%')
        ->orderBy('start_date', 'desc')
        ->first();

    // JIKA belum pernah diservis sama sekali (A), anggap butuh servis (true)
    if (!$lastService) {
        return true; 
    }

    // JIKA sudah pernah, cek apakah sudah lewat 6 bulan (B)
    return \Carbon\Carbon::parse($lastService->start_date)->addMonths(6)->isPast();
}


    
}