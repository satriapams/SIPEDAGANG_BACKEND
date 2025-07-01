<?php

namespace App\Http\Controllers;

use App\Models\PengaturanPengadaan;
use App\Models\Pengadaan;
use Illuminate\Http\Request;

class PengaturanPengadaanController extends Controller
{
    public function index()
    {
        $data = PengaturanPengadaan::all();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Belum ada data pengaturan.'], 200);
        }

        return response()->json(['message' => 'Data ditemukan', 'data' => $data], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_pengadaan_barang' => 'required|string',
            'satuan' => 'required|string|min:2|max:20',
            'harga_per_satuan' => 'required|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0|max:100',
            'pph' => 'nullable|numeric|min:0|max:100',
            'tanpa_pajak' => 'nullable|boolean',
        ]);

        $validated['jenis_pengadaan_barang'] = strtoupper($validated['jenis_pengadaan_barang']);
        $validated['satuan'] = strtoupper($validated['satuan']);
        $validated['tanpa_pajak'] = $request->boolean('tanpa_pajak', false);
        $validated['ppn'] = $validated['ppn'] ?? 12;
        $validated['pph'] = $validated['pph'] ?? 1.5;

        if (!preg_match('/^[A-Z]+$/', $validated['satuan'])) {
            return response()->json(['message' => 'Satuan hanya boleh huruf A-Z'], 422);
        }

        $exists = PengaturanPengadaan::where('jenis_pengadaan_barang', $validated['jenis_pengadaan_barang'])->first();
        if ($exists) {
            return response()->json([
                'message' => 'Jenis pengadaan barang sudah ada, tidak boleh duplikat.'
            ], 409);
        }

        $data = PengaturanPengadaan::create($validated);

        return response()->json([
            'message' => 'Pengaturan berhasil dibuat',
            'data' => $data
        ], 201);
    }

    public function show($id)
    {
        $item = PengaturanPengadaan::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['message' => 'Data ditemukan', 'data' => $item], 200);
    }

    public function update(Request $request, $id)
    {
        $item = PengaturanPengadaan::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'jenis_pengadaan_barang' => 'required|string',
            'satuan' => 'required|string|min:2|max:20',
            'harga_per_satuan' => 'required|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0|max:100',
            'pph' => 'nullable|numeric|min:0|max:100',
            'tanpa_pajak' => 'nullable|boolean',
        ]);

        $validated['jenis_pengadaan_barang'] = strtoupper($validated['jenis_pengadaan_barang']);
        $validated['satuan'] = strtoupper($validated['satuan']);
        $validated['tanpa_pajak'] = $request->boolean('tanpa_pajak', false);
        $validated['ppn'] = $validated['ppn'] ?? $item->ppn;
        $validated['pph'] = $validated['pph'] ?? $item->pph;

        if (!preg_match('/^[A-Z]+$/', $validated['satuan'])) {
            return response()->json(['message' => 'Satuan hanya boleh huruf A-Z'], 422);
        }

        $item->update($validated);

        // 🔁 Update semua pengadaan terkait jenis ini
        $pengadaans = Pengadaan::where('jenis_pengadaan_barang', $validated['jenis_pengadaan_barang'])->get();

        foreach ($pengadaans as $pengadaan) {
            preg_match('/([\d.]+)/', $pengadaan->jumlah_pembayaran, $matches);
            $jumlah = isset($matches[1]) ? (float)$matches[1] : 0;

            if ($validated['tanpa_pajak']) {
                // Jenis ini tidak kena pajak
                $pengadaan->update([
                    'harga_sebelum_pajak' => null,
                    'dpp' => null,
                    'ppn_total' => null,
                    'pph_total' => null,
                    'nominal' => round($jumlah * $validated['harga_per_satuan'], 2),
                ]);
            } else {
                $hargaSebelumPajak = $jumlah * $validated['harga_per_satuan'];
                $dpp = $hargaSebelumPajak * (100 / 111);
                $ppn = $dpp * ($validated['ppn'] / 100);
                $pph = $dpp * ($validated['pph'] / 100);
                $nominal = $dpp - $pph;

                $pengadaan->update([
                    'harga_sebelum_pajak' => round($hargaSebelumPajak, 2),
                    'dpp' => round($dpp, 2),
                    'ppn_total' => round($ppn, 2),
                    'pph_total' => round($pph, 2),
                    'nominal' => round($nominal, 2),
                ]);
            }
        }

        return response()->json([
            'message' => 'Pengaturan berhasil diperbarui dan semua pengadaan terkait telah dihitung ulang',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        $item = PengaturanPengadaan::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Pengaturan berhasil dihapus']);
    }
}
