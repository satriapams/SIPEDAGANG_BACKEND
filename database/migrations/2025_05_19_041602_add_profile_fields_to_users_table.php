<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('nama_pengguna');
            
            // Ubah phone_number: tidak nullable dan default-nya string kosong
            $table->string('phone_number')->default('')->after('profile_photo');
            
            $table->enum('status', ['active', 'inactive'])->default('active')->after('phone_number');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo', 'phone_number', 'status']);
        });
    }
};
