<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255'
        ]);

        Room::create($request->all());

        return back()->with('success', 'Ruangan berhasil ditambahkan');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return back()->with('success', 'Ruangan berhasil dihapus');
    }
}