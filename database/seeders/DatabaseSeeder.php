<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengadaan;
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

        // Seeder Admin
        for ($i = 1; $i <= 12; $i++) {
            User::create([
                'name' => "Admin $i",
                'nama_pengguna' => "admin$i",
                'password' => bcrypt("password$i"),
                'plain_password' => "password$i",
                'role' => 'admin',
                'status' => 'active'
            ]);
        }
    }
}
