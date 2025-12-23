<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('acs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->string('ac_type');          // Poin 2: Jenis AC (AC-2 PK)
    $table->string('model_type');       // Poin 3: Tipe AC (Split Wall)
    $table->string('brand');            // Poin 4: Brand (LG)
    $table->string('model');            // Poin 5: Model Unit (CU-PN18SKP)
    $table->string('indoor_sn');        // Poin 6: SN Indoor
    $table->string('outdoor_sn')->nullable(); // Poin 7: SN Outdoor
    $table->text('specifications');     // Poin 8: Spesifikasi
    $table->date('last_maintenance')->nullable();
    $table->string('image_indoor')->nullable();  // Poin 9
    $table->string('image_outdoor')->nullable(); // Poin 10
    $table->enum('status', ['baik', 'rusak'])->default('baik'); // Poin 11
    $table->decimal('x_position', 5, 2)->nullable();
    $table->decimal('y_position', 5, 2)->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
