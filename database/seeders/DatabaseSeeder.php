<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengadaan;
use App\Models\PengaturanPengadaan;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder Superadmin
        User::factory()->create([
            'name' => 'Superadmin',
            'nama_pengguna' => 'superadmin',
            'password' => bcrypt('superadmin123'),
            'plain_password' => 'superadmin123',
            'role' => 'superadmin',
            'status' => 'active'
        ]);

    }
}