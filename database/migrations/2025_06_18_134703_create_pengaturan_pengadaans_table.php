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
        Schema::create('pengaturan_pengadaans', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_pengadaan_barang')->unique(); // Tetap unique
            $table->string('satuan', 20)->default('KG'); // Bisa custom satuan seperti LITER/PCS
            $table->decimal('harga_per_satuan', 12, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(12.00); // Persen
            $table->decimal('pph', 5, 2)->default(1.50);  // Persen
            $table->boolean('tanpa_pajak')->default(false); // ✅ Tambahan untuk menandai tanpa perhitungan pajak
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_pengadaans');
    }
};
