<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterStatusEnumInResetRequestsTable extends Migration
{
    public function up()
    {
        // Menambahkan 'used' dan 'declined' ke enum status
        DB::statement("ALTER TABLE reset_requests MODIFY status ENUM('pending', 'approved', 'used', 'declined') NOT NULL");
    }

    public function down()
    {
        // Rollback ke enum awal tanpa 'used' dan 'declined'
        DB::statement("ALTER TABLE reset_requests MODIFY status ENUM('pending', 'approved') NOT NULL");
    }
}
