<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FloorController extends Controller
{
    /**
     * Update gambar denah lantai (Mendukung Image & PDF)
     */
    public function update(Request $request, Floor $floor)
    {
        // Penyesuaian validasi agar bisa menerima PDF (mimes:pdf)
        $request->validate([
            'floor_plan' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5MB
        ]);

        if ($request->hasFile('floor_plan')) {
            // Hapus denah lama jika ada untuk menghemat penyimpanan
            if ($floor->floor_plan) {
                Storage::disk('public')->delete($floor->floor_plan);
            }
            
            // Simpan file baru ke folder 'floor_plans' di disk public
            $path = $request->file('floor_plan')->store('floor_plans', 'public');
            
            // Update path di database
            $floor->update([
                'floor_plan' => $path
            ]);
        }

        return back()->with('success', 'Denah lantai berhasil diperbarui!');
    }
}