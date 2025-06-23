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

        // Data Pengaturan Pengadaan (buat jika belum ada)
        $jenisBarangList = [
            ['jenis_pengadaan_barang' => 'BERAS', 'satuan' => 'KG', 'harga_per_satuan' => 12000, 'ppn' => 12, 'pph' => 1.5],
            ['jenis_pengadaan_barang' => 'GABAH', 'satuan' => 'KG', 'harga_per_satuan' => 8000, 'ppn' => 12, 'pph' => 1.5],
            ['jenis_pengadaan_barang' => 'MINYAK', 'satuan' => 'LITER', 'harga_per_satuan' => 15000, 'ppn' => 12, 'pph' => 1.5],
            ['jenis_pengadaan_barang' => 'CUSTOM', 'satuan' => 'PCS', 'harga_per_satuan' => 20000, 'ppn' => 10, 'pph' => 2],
        ];

        foreach ($jenisBarangList as $barang) {
            PengaturanPengadaan::firstOrCreate(
                ['jenis_pengadaan_barang' => $barang['jenis_pengadaan_barang']],
                [
                    'satuan' => $barang['satuan'],
                    'harga_per_satuan' => $barang['harga_per_satuan'],
                    'ppn' => $barang['ppn'],
                    'pph' => $barang['pph'],
                ]
            );
        }

        // Buat 30 data pengadaan
        $admins = User::where('role', 'admin')->get();
        $adminCount = $admins->count();

        for ($i = 1; $i <= 30; $i++) {
            $admin = $admins[($i - 1) % $adminCount];
            $jenis = $jenisBarangList[($i - 1) % count($jenisBarangList)];

            $jumlah = rand(5, 15);
            $kuantum = $jumlah . ' ' . $jenis['satuan'];
            $hargaSebelumPajak = $jumlah * $jenis['harga_per_satuan'];
            $dpp = $hargaSebelumPajak * (100 / 111);
            $ppn = $dpp * ($jenis['ppn'] / 100);
            $pph = $dpp * ($jenis['pph'] / 100);
            $nominal = $dpp - $pph;

            Pengadaan::create([
                'nama_suplier' => 'PT Contoh Suplier ' . $i,
                'nama_perusahaan' => 'CV Perusahaan ' . $i,
                'jenis_bank' => 'BCA',
                'no_rekening' => '123456789' . $i,
                'atasnama_rekening' => 'Suplier ' . $i,
                'no_preorder' => now()->format('Y') . '/' . str_pad($i, 2, '0', STR_PAD_LEFT) . '/AUTO/' . now()->format('Y'),
                'tanggal_pengadaan' => now()->subDays($i),
                'jenis_pengadaan_barang' => $jenis['jenis_pengadaan_barang'],
                'kuantum' => $kuantum,
                'in_data' => json_encode([
                    [
                        'no_in' => 100 + $i,
                        'tanggal_in' => now()->subDays($i)->toDateString(),
                        'kuantum_in' => $kuantum,
                    ]
                ]),
                'jumlah_pembayaran' => $kuantum,
                'spp' => $kuantum,
                'harga_sebelum_pajak' => round($hargaSebelumPajak, 2),
                'dpp' => round($dpp, 2),
                'ppn_total' => round($ppn, 2),
                'pph_total' => round($pph, 2),
                'nominal' => round($nominal, 2),
                'user_id' => $admin->id,
            ]);
        }
    }
}