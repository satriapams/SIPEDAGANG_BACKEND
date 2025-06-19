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
            $table->string('jenis_pengadaan_barang')->unique(); // ✅ tambah unique
            $table->enum('satuan', ['KG', 'LITER', 'PCS'])->default('KG');
            $table->decimal('harga_per_satuan', 12, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(12.00); // dalam persen
            $table->decimal('pph', 5, 2)->default(1.5);   // dalam persen
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
