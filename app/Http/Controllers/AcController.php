<?php

namespace App\Http\Controllers;

use App\Models\Ac;
use App\Models\Room;
use App\Models\Building; // Tambahkan ini
use App\Exports\AcExport;
use App\Imports\AcImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcController extends Controller
{
    /**
     * Tampilkan halaman Master AC
     */
public function index()
{
    // Tambahkan 'schedules' ke dalam eager loading
    $all_acs = Ac::with(['room.floor.building', 'schedules' => function($q) {
        $q->wherePivot('status', 'selesai');
    }])->get();
    
    $buildings = Building::with('floors.rooms')->get();

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
            'image_indoor' => 'nullable|image|mimes:jpeg,png,jpg,heic,heif|max:5000',
            'image_outdoor' => 'nullable|image|mimes:jpeg,png,jpg,heic,heif|max:5000',
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
            'image_indoor' => 'nullable|image|mimes:jpeg,png,jpg,heic,heif|max:5000',
            'image_outdoor' => 'nullable|image|mimes:jpeg,png,jpg,heic,heif|max:5000',
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

    public function relocate(Request $request, $id)
{
    $request->validate([
        'target_ac_id' => 'required|exists:acs,id',
    ]);

    // 1. Ambil data AC yang ada di denah saat ini (AC A)
    $acA = Ac::findOrFail($id);

    // 2. Ambil data AC yang akan dipindahkan dari gedung/ruangan lain (AC B)
    $acB = Ac::findOrFail($request->target_ac_id);

    // 3. Simpan sementara data lokasi AC B agar tidak hilang saat ditimpa
    $oldRoomB = $acB->room_id;
    $oldXB = $acB->x_position;
    $oldYB = $acB->y_position;

    // 4. Pindahkan AC B ke posisi AC A
    $acB->update([
        'room_id' => $acA->room_id,
        'x_position' => $acA->x_position,
        'y_position' => $acA->y_position,
    ]);

    // 5. Pindahkan AC A ke posisi lama AC B (Tukar Posisi)
    // Sekarang AC A tidak dihapus, tapi pindah ke tempat asal AC B
    $acA->update([
        'room_id' => $oldRoomB,
        'x_position' => $oldXB,
        'y_position' => $oldYB,
    ]);

    return back()->with('success', 'Unit AC berhasil saling bertukar posisi!');
}


public function exportExcel() 
{
    return Excel::download(new AcExport, 'data-inventaris-ac.xlsx');
}

public function importExcel(Request $request)
{
    $request->validate(['file_excel' => 'required|mimes:xlsx,xls,csv']);

    $rows = Excel::toArray(new AcImport, $request->file('file_excel'))[0];
    $existingSns = Ac::pluck('indoor_sn')->toArray();
    $dataToReview = [];

    foreach ($rows as $row) {
        // 1. Cek duplikasi SN agar tidak double import
        if (in_array($row['sn_indoor'], $existingSns)) continue;

        // 2. Logika Auto-Create Lokasi (Gedung -> Lantai -> Ruangan)
        // Gunakan trim() untuk menghindari error karena spasi tidak sengaja di Excel
        $gedungName = trim($row['gedung']);
        $lantaiName = trim($row['lantai']);
        $ruanganName = trim($row['ruangan']);

        // A. Cari atau Buat Gedung
        $building = \App\Models\Building::firstOrCreate(
            ['name' => $gedungName],
            ['image' => 'buildings/default.png'] // Berikan default image jika wajib
        );

        // B. Cari atau Buat Lantai di dalam Gedung tersebut
        $floor = \App\Models\Floor::firstOrCreate([
            'building_id' => $building->id,
            'name' => $lantaiName
        ]);

        // C. Cari atau Buat Ruangan di dalam Lantai tersebut
        $room = \App\Models\Room::firstOrCreate([
            'floor_id' => $floor->id,
            'name' => $ruanganName
        ]);

        // 3. Masukkan ke array review
        $dataToReview[] = [
            'room_id'        => $room->id, 
            'room_name'      => $ruanganName,
            'building'       => $gedungName,
            'floor'          => $lantaiName,
            'ac_type'        => $row['tipe_ac'],
            'model_type'     => $row['model_type'],
            'brand'          => $row['brand'],
            'model'          => $row['model'],
            'indoor_sn'      => $row['sn_indoor'],
            'outdoor_sn'     => $row['sn_outdoor'],
            'specifications' => $row['spesifikasi'],
            'status'         => strtolower($row['status'] ?? 'baik'),
        ];
    }

    return response()->json(['success' => true, 'data' => $dataToReview]);
}

public function bulkStore(Request $request)
{
    $acs = $request->input('acs');

    if (!$acs || !is_array($acs)) {
        return response()->json(['success' => false, 'message' => 'Data kosong atau format salah']);
    }

    try {
        foreach ($acs as $item) {
            // Gunakan room_id dari item, pastikan ada fallback jika data excel tidak lengkap
            $finalRoomId = $item['room_id'] ?? null; 
            
            // Opsional: Jika room_id tidak ditemukan, cari ruangan default atau skip
            if (!$finalRoomId) continue;

            $imageIndoorPath = null;
            $imageOutdoorPath = null;

            // Proses upload gambar jika ada string base64
            if (!empty($item['image_indoor']) && str_starts_with($item['image_indoor'], 'data:image')) {
                $imageIndoorPath = $this->uploadBase64($item['image_indoor'], 'indoor');
            }

            if (!empty($item['image_outdoor']) && str_starts_with($item['image_outdoor'], 'data:image')) {
                $imageOutdoorPath = $this->uploadBase64($item['image_outdoor'], 'outdoor');
            }

            \App\Models\Ac::create([
                'room_id'        => $finalRoomId, 
                'ac_type'        => $item['ac_type'],
                'model_type'     => $item['model_type'],
                'brand'          => $item['brand'],
                'model'          => $item['model'],
                'indoor_sn'      => $item['indoor_sn'],
                'outdoor_sn'     => $item['outdoor_sn'] ?? '-',
                'specifications' => $item['specifications'] ?? '-',
                'status'         => $item['status'] ?? 'baik',
                'image_indoor'   => $imageIndoorPath,
                'image_outdoor'  => $imageOutdoorPath,
            ]);

            // Bersihkan variabel untuk menghemat RAM server saat loop besar
            unset($imageIndoorPath, $imageOutdoorPath, $item);
        }
        
        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        \Log::error("Bulk Store Error: " . $e->getMessage());
        return response()->json([
            'success' => false, 
            'message' => 'Gagal menyimpan batch: ' . $e->getMessage()
        ], 500);
    }
}

private function uploadBase64($base64String, $prefix)
{
    // Regex diperbarui untuk mendukung heic/heif
    if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
        $data = substr($base64String, strpos($base64String, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, heic, dll

        $data = base64_decode($data);
        $fileName = time() . '_' . $prefix . '_' . uniqid() . '.' . $type;
        
        // Simpan file asli
        Storage::disk('public')->put('ac_images/' . $fileName, $data);
        
        /**
         * CATATAN: Jika server Anda memiliki ImageMagick, 
         * Anda bisa mengonversi HEIC ke JPG di sini agar bisa tampil di web.
         */
        
        return 'ac_images/' . $fileName;
    }
    return null;
}




}