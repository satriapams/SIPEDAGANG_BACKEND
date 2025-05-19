<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStatusEnumInResetRequestsTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE reset_requests MODIFY status ENUM('pending', 'approved', 'used')");
    }

    public function down()
    {
        DB::statement("ALTER TABLE reset_requests MODIFY status ENUM('pending', 'approved')");
    }
}

