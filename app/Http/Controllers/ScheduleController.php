<?php

namespace App\Http\Controllers;

use App\Models\Ac;
use App\Models\Building;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $buildings = Building::with(['floors.rooms.acs'])->get();
        
        // 1. Ambil data mentah dengan relasi lengkap agar tidak terjadi N+1 query
        $rawSchedules = Schedule::with(['acs.room.floor.building'])->get();

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
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : '',
                'note' => $schedule->note ?? '',
                'day' => (int)$schedule->start_date->format('d'),
                'month' => (int)$schedule->start_date->format('m') - 1,
                'year' => (int)$schedule->start_date->format('Y'),
                'status' => $schedule->status,
                'locations' => $groupedLocations,
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
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : '',
                'status' => $schedule->status,
                'note' => $schedule->note ?? '',
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

        return view('schedule', compact('buildings', 'schedules_calendar', 'schedules_table'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'ac_ids' => 'required|array',
        ]);

        $schedule = Schedule::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'status' => 'belum',
            'note' => $request->notes,
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
        // 1. Update status Utama
        $schedule->update([
            'status' => $request->status,
            'end_date' => $request->status == 'selesai' ? now() : $schedule->end_date
        ]);

        // 2. PERBAIKAN: Jika status utama Selesai, paksa SEMUA AC di tabel pivot jadi Selesai
        if ($request->status === 'selesai') {
            $schedule->acs()->updateExistingPivot($schedule->acs->pluck('id'), ['status' => 'selesai']);
        }

        // 3. LOGIKA JADWAL OTOMATIS
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
        ]);

        // 1. Update data dasar
        $schedule->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->status === 'selesai' ? ($schedule->end_date ?? now()) : $request->end_date,
            'status' => $request->status,
            'note' => $request->note,
        ]);

        // 2. PERBAIKAN LOGIKA PIVOT:
        if ($request->has('ac_ids') && !empty($request->ac_ids)) {
            $pivotData = [];
            foreach ($request->ac_ids as $id) {
                // Jika status jadwal Selesai, maka AC tersebut WAJIB Selesai
                if ($request->status === 'selesai') {
                    $newStatus = 'selesai';
                } else {
                    // Jika tidak selesai, cek status individu dari request (jika ada) atau biarkan status lama
                    $newStatus = $request->status; // Menyamakan status AC dengan status Jadwal
                }

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