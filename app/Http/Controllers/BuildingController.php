<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::all(); 
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
            'image' => 'required|image|max:2048',
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

    public function update(Request $request, Building $building) {
        $building->update(['name' => $request->name]);
        return back()->with('success', 'Gedung diperbarui');
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return redirect()->route('location.index')->with('success', 'Gedung dihapus');
    }
}