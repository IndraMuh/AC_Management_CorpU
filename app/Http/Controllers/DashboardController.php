<?php

namespace App\Http\Controllers;

use App\Models\Ac;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total semua unit AC
        $totalAc = Ac::count();

        // 2. Total AC Baik (dari kolom status di tabel acs)
        $totalAcBaik = Ac::where('status', 'baik')->count();

        // 3. Total AC Rusak (dari kolom status di tabel acs)
        $totalAcRusak = Ac::where('status', 'rusak')->count();

        // 4. Perbaikan Total AC Proses
        // Kita hitung AC yang terhubung ke jadwal yang statusnya 'proses'
        // ATAU AC yang status di tabel pivotnya 'proses'
        $totalAcProses = DB::table('ac_schedule')
            ->join('schedules', 'ac_schedule.schedule_id', '=', 'schedules.id')
            ->where('schedules.status', 'proses') // Menghitung AC dalam jadwal yang sedang jalan
            ->orWhere('ac_schedule.status', 'proses') // Menghitung AC yang status individunya proses
            ->distinct('ac_schedule.ac_id')
            ->count();

        return view('dashboard', compact(
            'totalAc', 
            'totalAcBaik', 
            'totalAcRusak', 
            'totalAcProses'
        ));
    }
}