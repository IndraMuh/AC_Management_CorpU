<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AcController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/direct-password-update', [NewPasswordController::class, 'directUpdate'])
    ->name('password.direct_update');

Route::middleware('auth')->group(function () {
    
    // --- Profile Routes ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    // --- Manajemen Gedung & Lokasi (Location) ---
    Route::get('/location', [BuildingController::class, 'index'])->name('location.index');
    Route::get('/location/{building}', [BuildingController::class, 'show'])->name('location.show');
    Route::get('/buildings/{id}/floorplan', [BuildingController::class, 'floorplan'])->name('buildings.floorplan');
    
    // CRUD Gedung
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::patch('/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

    // --- Manajemen Lantai ---
    Route::post('/floors', [FloorController::class, 'store'])->name('floors.store');
    Route::patch('/floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
    Route::delete('/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');

    // --- Manajemen Ruangan ---
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    // --- Manajemen AC (Inventaris) ---
    Route::get('/master-ac', [AcController::class, 'index'])->name('ac.index');
    Route::post('/ac', [AcController::class, 'store'])->name('ac.store');
    Route::patch('/ac/{ac}', [AcController::class, 'update'])->name('ac.update');
    Route::delete('/ac/{ac}', [AcController::class, 'destroy'])->name('ac.destroy');
    Route::get('/export-ac', [AcController::class, 'exportExcel'])->name('ac.export');
    Route::post('/import-ac', [AcController::class, 'importExcel'])->name('ac.import');
    Route::post('/bulk-store-ac', [AcController::class, 'bulkStore'])->name('ac.bulk-store');

    Route::resource('schedules', ScheduleController::class);
    
    // Route khusus untuk update status cepat (untuk checkbox/status per AC)
    Route::patch('/schedules/{schedule}/update-status', [ScheduleController::class, 'updateStatus'])
        ->name('schedules.update-status');

    Route::get('/history', [ActivityLogController::class, 'index'])->name('history.index');
    Route::get('/ac-history/{id}', [HistoryController::class, 'getAcHistory']);

    // --- Fitur Map & Denah ---
    Route::patch('/ac/{ac}/update-position', [AcController::class, 'updatePosition'])->name('ac.update-position');
    Route::post('/ac/{id}/relocate', [AcController::class, 'relocate'])->name('ac.relocate');

    Route::get('/system-logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // TAMBAHKAN ROUTE INI DI SINI:
    Route::patch('/floors/{floor}/update-rotation', function (\App\Models\Floor $floor, \Illuminate\Http\Request $request) {
        $floor->update([
            'rotation' => $request->rotation
        ]);
        return response()->json(['success' => true, 'message' => 'Rotation updated']);
    });
});

require __DIR__.'/auth.php';