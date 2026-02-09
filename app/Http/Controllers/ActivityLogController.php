<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller {
    public function index(Request $request) {
        $query = Activity::with('causer')->latest();

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject_type', 'like', "%{$search}%")
                  ->orWhereHas('causer', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // UBAH DISINI: Gunakan paginate() dan pertahankan query string saat pindah halaman
        $logs = $query->paginate(10)->withQueryString();

        return view('history', compact('logs'));
    }
}