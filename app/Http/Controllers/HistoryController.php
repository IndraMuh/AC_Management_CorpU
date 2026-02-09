<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index()
    {
        // Ambil jadwal beserta AC terkait
        $schedules = Schedule::with(['acs.room.floor.building'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('history', compact('schedules'));
    }

// Tambahkan/Update di HistoryController.php
public function getAcHistory($id)
{
    $ac = \App\Models\Ac::with(['schedules' => function($query) {
        // PENTING: Harus ada withPivot('status')
$query->withPivot('status')
              ->wherePivot('status', 'selesai') 
              ->orderBy('start_date', 'desc');
    }])->find($id);

    if (!$ac) return response()->json(['error' => 'Not Found'], 404);

    return response()->json([
        'brand' => $ac->brand,
        'sn' => $ac->indoor_sn,
        'history' => $ac->schedules->map(function($s) {
            return [
                'date' => $s->start_date ? $s->start_date->format('Y-m-d') : '-',
                'name' => $s->name,
                'work' => $s->worker_name ?? ' - ',
                // Di sini data diambil dari tabel pivot ac_schedule
                'status' => $s->pivot->status ?? 'belum',
                'proof' => $s->proof_image 
            ];
        })
    ]);
}
}