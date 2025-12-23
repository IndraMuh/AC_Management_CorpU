<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['floor_id', 'name'];

    // Relasi: Ruangan milik sebuah lantai
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    // Relasi: Satu ruangan memiliki banyak AC
    public function acs(): HasMany
    {
        return $this->hasMany(Ac::class);
    }
}