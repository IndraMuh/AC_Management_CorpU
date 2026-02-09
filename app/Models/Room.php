<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity; // Tambahkan ini
use Spatie\Activitylog\LogOptions; // Tambahkan ini

class Room extends Model
{
    use LogsActivity;    
    protected $fillable = ['floor_id', 'name'];

    // Relasi: Ruangan milik sebuah lantai
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function getActivitylogOptions(): LogOptions // Tambahkan fungsi ini
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relasi: Satu ruangan memiliki banyak AC
    public function acs(): HasMany
    {
        return $this->hasMany(Ac::class);
    }
}