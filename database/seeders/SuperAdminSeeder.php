<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        User::create([
            'name' => 'Super Admin',
            'nama_pengguna' => 'superadmin',
            'password' => Hash::make('bulog123'),
            'role' => 'superadmin',
            'status' => 'active'
        ]);
    }
}
