<?php

namespace App\Http\Controllers;

use App\Models\Ac;
use App\Models\Room;
use App\Models\Building; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcController extends Controller
{
    /**
     * Tampilkan halaman Master AC
     */
    public function index()
    {
        // Ambil semua AC dengan relasi agar filter gedung/ruangan di view berfungsi
        $all_acs = Ac::with('room.floor.building')->get();
        
        // Ambil semua gedung untuk dropdown pilihan lokasi di modal tambah/edit
        $buildings = Building::with('floors.rooms')->get();

        // Pastikan nama view sesuai dengan file blade kamu (master-ac.blade.php)
        return view('master-ac', compact('all_acs', 'buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'ac_type' => 'required|string',
            'model_type' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'indoor_sn' => 'required|string',
            'outdoor_sn' => 'nullable|string',
            'specifications' => 'nullable|string',
            'status' => 'required|in:baik,rusak',
            'image_indoor' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_outdoor' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image_indoor')) {
            $validated['image_indoor'] = $request->file('image_indoor')->store('ac_images', 'public');
        }

        if ($request->hasFile('image_outdoor')) {
            $validated['image_outdoor'] = $request->file('image_outdoor')->store('ac_images', 'public');
        }

        Ac::create($validated);

        return redirect()->back()->with('success', 'Data AC berhasil ditambahkan!');
    }

    public function updatePosition(Request $request, $id)
    {
        $ac = Ac::findOrFail($id);
        
        $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric'
        ]);

        $ac->update([
            'x_position' => $request->x,
            'y_position' => $request->y
        ]);

        return response()->json(['message' => 'Posisi AC diperbarui']);
    }

    public function update(Request $request, $id)
    {
        $ac = Ac::findOrFail($id);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'ac_type' => 'required|string|max:255',
            'model_type' => 'required|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'indoor_sn' => 'required|string|max:255',
            'outdoor_sn' => 'nullable|string|max:255',
            'status' => 'required|in:baik,rusak',
            'specifications' => 'nullable|string',
            'image_indoor' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_outdoor' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image_indoor')) {
            if ($ac->image_indoor) Storage::disk('public')->delete($ac->image_indoor);
            $validated['image_indoor'] = $request->file('image_indoor')->store('ac_photos', 'public');
        }

        if ($request->hasFile('image_outdoor')) {
            if ($ac->image_outdoor) Storage::disk('public')->delete($ac->image_outdoor);
            $validated['image_outdoor'] = $request->file('image_outdoor')->store('ac_photos', 'public');
        }

        $ac->update($validated);

        return back()->with('success', 'Data AC berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $ac = Ac::findOrFail($id);

        if ($ac->image_indoor) Storage::disk('public')->delete($ac->image_indoor);
        if ($ac->image_outdoor) Storage::disk('public')->delete($ac->image_outdoor);

        $ac->delete();

        return back()->with('success', 'Unit AC berhasil dihapus!');
    }
}