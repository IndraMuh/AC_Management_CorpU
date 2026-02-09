<?php

namespace App\Exports;

use App\Models\Ac;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class AcExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithDrawings, WithEvents
{
    private $rows;

    public function collection()
    {
        // Mengambil data dengan eager loading untuk performa
        $this->rows = Ac::with(['room.floor.building', 'schedules' => function($query) {
            $query->orderBy('start_date', 'desc');
        }])->get();

        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastColumn = 'R';
                $lastRow = $this->rows->count() + 1;
                $sheet = $event->sheet->getDelegate();

                // 1. Aktifkan AutoFilter
                $sheet->setAutoFilter("A1:{$lastColumn}1");

                // 2. Zebra Stripes (Baris Selang-seling)
                for ($i = 2; $i <= $lastRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F8FAFC'); // Light Slate Grey
                    }
                }

                // 3. Conditional Formatting untuk Kolom Status (N)
                for ($i = 2; $i <= $lastRow; $i++) {
                    $cell = 'N' . $i;
                    $status = strtoupper($sheet->getCell($cell)->getValue());
                    
                    if (str_contains($status, 'NORMAL') || str_contains($status, 'HIDUP')) {
                        $sheet->getStyle($cell)->getFont()->getColor()->setARGB('15803D'); // Hijau Sukses
                        $sheet->getStyle($cell)->getFont()->setBold(true);
                    } elseif (str_contains($status, 'RUSAK') || str_contains($status, 'MATI')) {
                        $sheet->getStyle($cell)->getFont()->getColor()->setARGB('B91C1C'); // Merah Error
                        $sheet->getStyle($cell)->getFont()->setBold(true);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Inventaris AC';
    }

    public function headings(): array
    {
        return [
            'ID', 'GEDUNG', 'LANTAI', 'RUANGAN', 'TIPE AC', 'MODEL TYPE', 
            'BRAND', 'MODEL', 'SN INDOOR', 'SN OUTDOOR', 'SPESIFIKASI', 
            'TGL TERAKHIR', 'TGL BERIKUTNYA', 'STATUS', 
            'FOTO INDOOR', 'FOTO OUTDOOR', 'RIWAYAT SERVICE', 'RIWAYAT PERBAIKAN'
        ];
    }

    public function map($ac): array
    {
        // Filter history berdasarkan kategori
        $serviceHistories = $ac->schedules->filter(function ($s) {
            $isService = stripos($s->name, 'service') !== false || stripos($s->name, 'servis') !== false;
            return $isService && strtolower($s->pivot->status) === 'selesai';
        });

        $repairHistories = $ac->schedules->filter(function ($s) {
            $isRepair = stripos($s->name, 'service') === false && stripos($s->name, 'servis') === false;
            return $isRepair && strtolower($s->pivot->status) === 'selesai';
        });

        $lastServiceFromHistory = $serviceHistories->first(); 
        $lastMaintenanceDate = $lastServiceFromHistory ? $lastServiceFromHistory->start_date : $ac->last_maintenance;

        // Formatter teks histori
        $formatLog = function($collection) {
            return $collection->map(function ($s) {
                $date = $s->start_date instanceof Carbon ? $s->start_date : Carbon::parse($s->start_date);
                return "● [" . $date->format('d/m/Y') . "] " . strtoupper($s->name) . 
                       ($s->worker_name ? "\n  Teknisi: " . $s->worker_name : "") . 
                       ($s->note ? "\n  Ket: " . $s->note : "");
            })->implode("\n\n");
        };

        return [
            $ac->id,
            $ac->room->floor->building->name ?? '-',
            $ac->room->floor->name ?? '-',
            $ac->room->name ?? '-',
            $ac->ac_type,
            $ac->model_type,
            $ac->brand,
            $ac->model,
            $ac->indoor_sn,
            $ac->outdoor_sn,
            $ac->specifications,
            $lastMaintenanceDate ? Carbon::parse($lastMaintenanceDate)->format('d/m/Y') : '-',
            $ac->next_service_date ? Carbon::parse($ac->next_service_date)->format('d/m/Y') : '-',
            strtoupper($ac->status),
            '', // Placeholder Foto Indoor
            '', // Placeholder Foto Outdoor
            $formatLog($serviceHistories),
            $formatLog($repairHistories)
        ];
    }

    public function drawings()
    {
        $drawings = [];
        foreach ($this->rows as $index => $ac) {
            $rowNumber = $index + 2;
            $photoColumns = ['O' => $ac->image_indoor, 'P' => $ac->image_outdoor];

            foreach ($photoColumns as $col => $path) {
                if ($path && file_exists(storage_path('app/public/' . $path))) {
                    $drawing = new Drawing();
                    $drawing->setPath(storage_path('app/public/' . $path));
                    $drawing->setHeight(90); // Ukuran foto profesional
                    $drawing->setCoordinates($col . $rowNumber); 
                    $drawing->setOffsetX(12);
                    $drawing->setOffsetY(10);
                    $drawings[] = $drawing;
                }
            }
        }
        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = 'R';

        $sheet->freezePane('A2');

        // Mengatur tinggi baris agar foto & teks histori terlihat lega
        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(115);
        }

        // Style Header (Modern Dark)
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true, 
                'color' => ['rgb' => 'FFFFFF'], 
                'size' => 10
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID, 
                'startColor' => ['rgb' => '1E293B'] // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // Style Global Body
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN, 
                    'color' => ['rgb' => 'E2E8F0'] // Light Border
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
                'indent' => 1
            ],
            'font' => [
                'size' => 9,
                'name' => 'Arial'
            ]
        ]);

        // Pengaturan Lebar Kolom Khusus
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('K')->setWidth(30);
        $sheet->getColumnDimension('O')->setWidth(28); // Foto
        $sheet->getColumnDimension('P')->setWidth(28); // Foto
        $sheet->getColumnDimension('Q')->setWidth(50); // Log Service
        $sheet->getColumnDimension('R')->setWidth(50); // Log Perbaikan

        // Perataan Tengah untuk Data Singkat
        $centerCols = ['A', 'B', 'C', 'L', 'M', 'N'];
        foreach ($centerCols as $col) {
            $sheet->getStyle($col . '2:' . $col . $lastRow)
                  ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
} 