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
        Schema::create('data_pemohons', function (Blueprint $table) {
            $table->id();
            $table->string('nama_suplier');
            $table->string('nama_perusahaan')->unique();
            $table->string('jenis_bank');
            $table->string('no_rekening');
            $table->string('atasnama_rekening');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pemohons');
    }
};
