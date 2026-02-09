<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_xx_xx_create_schedules_table.php
public function up()
{
    Schema::create('schedules', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('worker_name')->nullable();
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->text('note')->nullable();
        $table->text('proof_image')->nullable();
        // Menambahkan 'belum' agar sesuai dengan logika Alpine.js Anda
        $table->enum('status', ['belum', 'proses', 'selesai'])->default('belum');
        $table->timestamps();
    });

    Schema::create('ac_schedule', function (Blueprint $table) {
        $table->id();
        $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
        $table->foreignId('ac_id')->constrained('acs')->onDelete('cascade');
        
        // PENTING: Tambahkan status per AC di sini
        $table->string('status')->default('belum'); // 'belum' atau 'selesai'
        $table->timestamps();
    });
}

public function down(): void
{
    // Hapus tabel pivot dulu baru tabel utama
    Schema::dropIfExists('ac_schedule');
    Schema::dropIfExists('schedules');
}

};
