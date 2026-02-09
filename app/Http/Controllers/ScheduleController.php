<?php

namespace App\Http\Controllers;

use App\Models\Ac;
use App\Models\Building;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ScheduleController extends Controller
{
    public function index()
    {
        $buildings = Building::with(['floors.rooms.acs'])->get();
        
        // 1. Ambil data mentah dengan relasi lengkap agar tidak terjadi N+1 query
$rawSchedules = Schedule::with(['acs.room.floor.building'])
                            ->latest() // Mengurutkan berdasarkan created_at DESC (Terbaru)
                            ->get();

// 1. Jadwal Seminggu Lagi
    $upcomingServices = Schedule::whereBetween('start_date', [
        now()->toDateTimeString(), 
        now()->addDays(7)->toDateTimeString()
    ])->where('status', '!=', 'selesai')->get();

    // 2. AC yang lewat 6 bulan (Overdue)
    // Kita ambil semua AC dan filter berdasarkan accessor needs_service
// Di dalam public function index()
// File: app/Http/Controllers/ScheduleController.php
$overdueAcs = \App\Models\Ac::with(['room.floor.building', 'schedules' => function($q) {
    $q->wherePivot('status', 'selesai')
      ->where('name', 'like', '%service%')
      ->orderBy('start_date', 'desc');
}])->get()->filter(function($ac) {
    return $ac->needs_service; 
});

        // 2. Pemetaan data untuk tampilan Kalender
        $schedules_calendar = $rawSchedules->map(function ($schedule) {
            $groupedLocations = $schedule->acs->groupBy(function ($ac) {
                return $ac->room->floor->building->name;
            })->map(function ($acsInBuilding, $buildingName) {
                return [
                    'name' => $buildingName,
                    'floors' => $acsInBuilding->groupBy(function ($ac) {
                        return $ac->room->floor->name;
                    })->map(function ($acsInFloor, $floorName) {
                        return [
                            'name' => $floorName,
                            'rooms' => $acsInFloor->groupBy(function ($ac) {
                                return $ac->room->name;
                            })->map(function ($acsInRoom, $roomName) {
                                return [
                                    'name' => $roomName,
                                    'acs' => $acsInRoom->map(function ($ac) {
                                        return [
                                            'id' => $ac->id,
                                            'name' => $ac->ac_type . ' ' . $ac->brand,
                                            'brand' => $ac->brand,
                                            'ac_type' => $ac->ac_type,
                                            'model' => $ac->model,
                                            'status' => $ac->pivot->status ?? 'belum',
                                            'sn_indoor' => $ac->indoor_sn, 
                                            'sn_outdoor' => $ac->outdoor_sn,
                                            'image' => $ac->image_indoor,
                                            'next_service_date' => $ac->next_service_date, 
                                        ];
                                    })->values()
                                ];
                            })->values()
                        ];
                    })->values()
                ];
            })->values();

            return [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'worker_name' => $schedule->worker_name,
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : '',
                'note' => $schedule->note ?? '',
                'day' => (int)$schedule->start_date->format('d'),
                'month' => (int)$schedule->start_date->format('m') - 1,
                'year' => (int)$schedule->start_date->format('Y'),
                'status' => $schedule->status,
                'locations' => $groupedLocations,
                'proof_image' => $schedule->proof_image,
                'acs' => $schedule->acs->map(fn($ac) => [
                    'id' => $ac->id,
                    'status' => $ac->pivot->status
                ]),
            ];
        });

        // 3. Pemetaan data untuk tampilan Tabel
        $schedules_table = $rawSchedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'worker_name' => $schedule->worker_name,
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : '',
                'status' => $schedule->status,
                'note' => $schedule->note ?? '',
                'proof_image' => $schedule->proof_image,
                'acs' => $schedule->acs->map(function ($ac) {
                    return [
                        'id' => $ac->id,
                        'brand' => $ac->brand,
                        'model' => $ac->model,
                        'ac_type' => $ac->ac_type,
                        'sn_indoor' => $ac->indoor_sn,
                        'sn_outdoor' => $ac->outdoor_sn, 
                        'image' => $ac->image_indoor,
                        'status' => $ac->pivot->status ?? 'belum',
                        'next_service_date' => $ac->next_service_date,
                        'room' => [
                            'name' => $ac->room->name,
                            'floor' => [
                                'name' => $ac->room->floor->name,
                                'building' => [
                                    'name' => $ac->room->floor->building->name
                                ]
                            ]
                        ]
                    ];
                })
            ];
        });

return view('schedule', compact(
        'buildings', 
        'schedules_calendar', 
        'schedules_table', 
        'upcomingServices', // Kirim ke view
        'overdueAcs'        // Kirim ke view
    ));
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'worker_name' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'ac_ids' => 'required|array',
        ]);

        $schedule = Schedule::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'status' => 'belum',
            'note' => $request->notes,
            'worker_name' => $request->worker_name,
        ]);

        $acIds = $request->ac_ids;
        $pivotData = [];
        foreach ($acIds as $id) {
            $pivotData[$id] = ['status' => 'belum'];
        }
        $schedule->acs()->attach($pivotData);

        if (preg_match('/\bservice\b/i', $request->name)) {
            $futureSchedules = Schedule::where('id', '!=', $schedule->id)
                ->where('start_date', '>', $request->start_date)
                ->where('status', 'belum')
                ->where(function($q) {
                    $q->where('name', 'like', '%Rutin%')
                      ->orWhere('name', 'like', '%Service%');
                })
                ->with('acs')
                ->get();

            foreach ($futureSchedules as $futureSchedule) {
                $futureSchedule->acs()->detach($acIds);
                $futureSchedule->load('acs');
                if ($futureSchedule->acs->count() === 0) {
                    $futureSchedule->delete();
                }
            }
            Ac::whereIn('id', $acIds)->update(['next_service_date' => null]);
        }

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

public function updateStatus(Request $request, Schedule $schedule)
{
    // 1. Update status Utama (Jadwal)
    $schedule->update([
        'status' => $request->status,
        'end_date' => $request->status == 'selesai' ? now() : $schedule->end_date
    ]);

    // 2. FITUR BARU: Simpan status masing-masing AC dari Modal ke Database (Pivot)
    // Ini yang membuat centang tidak hilang saat di-refresh
    if ($request->has('ac_statuses')) {
        foreach ($request->ac_statuses as $acId => $acStatus) {
            $schedule->acs()->updateExistingPivot($acId, ['status' => $acStatus]);
        }
    }

    // 3. Jika status utama Selesai, paksa SEMUA AC di tabel pivot jadi Selesai (Backup)
    if ($request->status === 'selesai') {
        $schedule->acs()->updateExistingPivot($schedule->acs->pluck('id'), ['status' => 'selesai']);
    }

    // 4. LOGIKA JADWAL OTOMATIS (Tetap sama)
    if ($request->status == 'selesai' && preg_match('/\bservice\b/i', $schedule->name)) {
        $nextDate = \Carbon\Carbon::now()->addMonths(6);
        $newSchedule = Schedule::create([
            'name' => 'Rutin: ' . $schedule->name,
            'start_date' => $nextDate,
            'status' => 'belum',
            'note' => 'Jadwal rutin otomatis dari servis terakhir tanggal ' . now()->format('d/m/Y'),
        ]);

        $acIds = $schedule->acs->pluck('id')->toArray();
        $pivotData = [];
        foreach ($acIds as $id) {
            $pivotData[$id] = ['status' => 'belum'];
        }
        $newSchedule->acs()->attach($pivotData);

        foreach ($schedule->acs as $ac) {
            $ac->update(['next_service_date' => $nextDate]);
        }
    }

    return redirect()->back()->with('success', 'Status diperbarui!');
}

public function update(Request $request, Schedule $schedule)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'start_date' => 'required|date',
        'status' => 'required|in:belum,proses,selesai',
        'ac_ids' => 'array',
        'proof_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5000',
        'existing_images' => 'array' // Tambahkan validasi untuk array gambar lama
    ]);

    $updateData = [
        'name' => $request->name,
        'start_date' => $request->start_date,
        'end_date' => $request->status === 'selesai' ? ($schedule->end_date ?? now()) : $request->end_date,
        'status' => $request->status,
        'note' => $request->note,
        'worker_name' => $request->worker_name,
    ];

    // --- LOGIKA HAPUS & UPDATE GAMBAR ---
    
    // 1. Ambil daftar gambar yang saat ini ada di database
    $oldImages = is_array($schedule->proof_image) ? $schedule->proof_image : [];
    
    // 2. Ambil daftar gambar yang dipertahankan dari frontend (dikirim via input hidden)
    $keptImages = $request->input('existing_images', []);

    // 3. Identifikasi gambar yang dihapus (ada di DB tapi tidak ada di kiriman form)
    $imagesToDelete = array_diff($oldImages, $keptImages);
    foreach ($imagesToDelete as $fileToDelete) {
        if (Storage::disk('public')->exists($fileToDelete)) {
            Storage::disk('public')->delete($fileToDelete);
        }
    }

    // 4. Proses upload gambar baru (jika ada)
    $newImages = $keptImages; // Mulai dengan gambar yang dipertahankan
    if ($request->hasFile('proof_images')) {
        foreach ($request->file('proof_images') as $file) {
            $path = $file->store('proofs', 'public');
            $newImages[] = $path;
        }
    }

    // Simpan hasil gabungan (gambar lama yang tersisa + gambar baru) ke array update
    $updateData['proof_image'] = $newImages;

    // 1. Update data dasar ke database
    $schedule->update($updateData);

    // 2. LOGIKA PIVOT AC (Tetap sama)
    if ($request->has('ac_ids') && !empty($request->ac_ids)) {
        $pivotData = [];
        foreach ($request->ac_ids as $id) {
            $newStatus = ($request->status === 'selesai') ? 'selesai' : $request->status;
            $pivotData[$id] = ['status' => $newStatus];
        }
        $schedule->acs()->sync($pivotData);
    } else {
        $schedule->acs()->detach();
    }

    if ($schedule->acs()->count() === 0) {
        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal dihapus karena tidak ada AC.');
    }

    return redirect()->back()->with('success', 'Data berhasil diperbarui!');
}

    public function destroy(Schedule $schedule)
    {
        $schedule->acs()->detach(); 
        $schedule->delete(); 
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus!');
    }
}