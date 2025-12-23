<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date', 'note', 'status'];

    /**
     * Agar Laravel otomatis mengubah string tanggal menjadi objek Carbon
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
}