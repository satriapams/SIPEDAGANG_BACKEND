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
        Schema::create('pengadaan', function (Blueprint $table) {
            $table->id();
    
            // Data supplier
            $table->string('nama_suplier');
            $table->string('nama_perusahaan');
            $table->string('jenis_bank');
            $table->string('no_rekening');
    
            // No preorder dan pengadaan
            $table->string('no_preorder');
            $table->date('tanggal_pengadaan');
            $table->string('jenis_pengadaan_barang');
            $table->string('kuantum');
    
            // Kolom IN (bisa disimpan sebagai JSON)
            $table->json('in_data')->nullable();
    
            // Pembayaran
            $table->string('jumlah_pembayaran');
            $table->integer('spp');
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengadaan');
    }
};
