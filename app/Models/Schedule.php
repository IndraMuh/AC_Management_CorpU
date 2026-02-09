<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity; // Tambahkan ini
use Spatie\Activitylog\LogOptions; // Tambahkan ini

class Schedule extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = ['name', 'start_date', 'end_date', 'note', 'status','worker_name','proof_image'];

    /**
     * Agar Laravel otomatis mengubah string tanggal menjadi objek Carbon
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'proof_image' => 'array',
    ];
    
    /**
     * Relasi ke AC dengan menyertakan kolom status di tabel pivot
    */
    public function acs()
    {
        // Pastikan menambahkan ->withPivot('status') 
        // agar kita bisa mengakses $ac->pivot->status
        return $this->belongsToMany(Ac::class, 'ac_schedule')
        ->withPivot('status')
        ->withTimestamps();
        }
        public function getActivitylogOptions(): LogOptions // Tambahkan fungsi ini
        {
            return LogOptions::defaults()
                ->logAll()
                ->logOnlyDirty()
                ->dontSubmitEmptyLogs();
        }
        }