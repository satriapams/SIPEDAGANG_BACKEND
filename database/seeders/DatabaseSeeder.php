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

        $banks = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
        $satuan = ['KG', 'LITER', 'PCS'];

        // Seeder Pengadaan
        for ($i = 1; $i <= 30; $i++) {
            $kuantumValue = rand(10, 100);
            $kuantumUnit = $satuan[array_rand($satuan)];
            $kuantum = "$kuantumValue $kuantumUnit";

            $in_kuantumValue = rand(1, $kuantumValue);
            $in_kuantum = "$in_kuantumValue $kuantumUnit";

            $tanggal_pengadaan = Carbon::now()->subDays(rand(1, 30))->format('Y-m-d');
            $tanggal_in = Carbon::parse($tanggal_pengadaan)->addDays(rand(1, 3))->format('Y-m-d');

            Pengadaan::create([
                'nama_suplier' => "Suplier $i",
                'nama_perusahaan' => 'PT Sumber Makmur Abadi',
                'jenis_bank' => $banks[array_rand($banks)],
                'no_rekening' => rand(1000000000, 9999999999),
                'no_preorder' => sprintf('%04d/%02d/PO%03d/2025', rand(1000, 9999), rand(1, 12), rand(100, 999)),
                'tanggal_pengadaan' => $tanggal_pengadaan,
                'jenis_pengadaan_barang' => "Barang $i",
                'kuantum' => $kuantum,
                'in_data' => json_encode([
                    [
                        'no_in' => rand(1000, 9999),
                        'tanggal_in' => now()->subDays(rand(0, 10))->toDateString(),
                        'kuantum_in' => rand(10, 100) . ' KG',
                    ]
                ]),

                'jumlah_pembayaran' => rand(1000000, 10000000) . ' IDR',
                'spp' => rand(1, 100),
                'user_id' => User::inRandomOrder()->where('role', 'admin')->first()->id
            ]);
        }
    }
}
