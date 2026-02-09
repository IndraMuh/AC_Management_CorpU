<?php

namespace App\Imports;

use App\Models\Ac;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AcImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Method ini dibiarkan kosong karena kita akan memproses 
        // koleksi langsung di Controller untuk keperluan review.
        return $rows;
    }

    public function model(array $row)
    {
        // Pastikan nama kolom di array $row sesuai dengan header di file Excel Anda (lowercase)
        return new Ac([
            'room_id'        => $row['room_id'], 
            'brand'          => $row['brand'],
            'model'          => $row['model'],
            'ac_type'        => $row['ac_type'],
            'model_type'     => $row['model_type'] ?? null,
            'indoor_sn'      => $row['indoor_sn'],
            'outdoor_sn'     => $row['outdoor_sn'],
            'specifications' => $row['specifications'] ?? null,
            'status'         => strtolower($row['status'] ?? 'baik'),
        ]);
    }
}