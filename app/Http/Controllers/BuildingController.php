<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::orderBy('name', 'asc')->get();
        return view('location', compact('buildings'));
    }

    public function show(Building $building)
    {
        $building->load(['floors.rooms.acs']); 
        return view('location-detail', compact('building'));
    }

    public function floorplan($id)
    {
        $building = Building::with(['floors.rooms.acs'])->findOrFail($id);
        // Disesuaikan dengan gambar: file ada di resources/views/floorplan.blade.php
        return view('floorplan', compact('building'));
    }

    public function store(Request $request) 
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required|image|max:5000',
            'floors' => 'required|array|min:1'
        ]);

        $buildingData = ['name' => $request->name];
        if ($request->hasFile('image')) {
            $buildingData['image'] = $request->file('image')->store('buildings', 'public');
        }

        $building = Building::create($buildingData);

        foreach ($request->floors as $floorName) {
            $building->floors()->create(['name' => $floorName]);
        }

        return back()->with('success', 'Gedung berhasil ditambahkan!');
    }

    public function update(Request $request, Building $building)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5000',
        'new_floors' => 'nullable|array',
        'delete_floors' => 'nullable|array',
    ]);

    // 1. Update Info Gedung
    $building->name = $request->name;
    if ($request->hasFile('image')) {
        $building->image = $request->file('image')->store('buildings', 'public');
    }
    $building->save();

    // 2. Hapus Lantai yang ditandai (Jika ada)
    if ($request->has('delete_floors')) {
        // Ini akan menghapus lantai berdasarkan ID yang dikirim
        Floor::whereIn('id', $request->delete_floors)->delete();
    }

    // 3. Tambah Lantai Baru (Hanya jika input tidak kosong)
    if ($request->has('new_floors')) {
        foreach ($request->new_floors as $floorName) {
            if ($floorName) {
                $building->floors()->create(['name' => $floorName]);
            }
        }
    }

    return back()->with('success', 'Gedung berhasil diperbarui!');
}

    public function destroy(Building $building)
    {
        $building->delete();
        return redirect()->route('location.index')->with('success', 'Gedung dihapus');
    }
}