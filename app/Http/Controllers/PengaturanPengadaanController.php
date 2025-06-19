<?php

namespace App\Http\Controllers;

use App\Models\PengaturanPengadaan;
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
            'satuan' => 'required|in:KG,LITER,PCS',
            'harga_per_satuan' => 'required|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0|max:100',
            'pph' => 'nullable|numeric|min:0|max:100',
        ]);

        // Cegah duplikasi jenis_pengadaan_barang
        $existing = PengaturanPengadaan::where('jenis_pengadaan_barang', strtoupper($validated['jenis_pengadaan_barang']))->first();
        if ($existing) {
            return response()->json([
                'message' => 'Jenis pengadaan barang sudah ada, tidak boleh duplikat.'
            ], 409);
        }

        $validated['jenis_pengadaan_barang'] = strtoupper($validated['jenis_pengadaan_barang']);
        $validated['ppn'] = $validated['ppn'] ?? 12;
        $validated['pph'] = $validated['pph'] ?? 1.5;

        $data = PengaturanPengadaan::create($validated);

        if ($data) {
            return response()->json([
                'message' => 'Pengaturan berhasil dibuat',
                'data' => $data
            ], 201);
        } else {
            return response()->json([
                'message' => 'Gagal menyimpan data'
            ], 500);
        }
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
            'satuan' => 'required|in:KG,LITER,PCS',
            'harga_per_satuan' => 'required|numeric|min:0',
            'ppn' => 'nullable|numeric|min:0|max:100',
            'pph' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['jenis_pengadaan_barang'] = strtoupper($validated['jenis_pengadaan_barang']);
        $validated['ppn'] = $validated['ppn'] ?? $item->ppn;
        $validated['pph'] = $validated['pph'] ?? $item->pph;

        $success = $item->update($validated);

        if ($success) {
            return response()->json([
                'message' => 'Pengaturan berhasil diperbarui',
                'data' => $item
            ]);
        } else {
            return response()->json(['message' => 'Gagal memperbarui data'], 500);
        }
    }

    public function destroy($id)
    {
        $item = PengaturanPengadaan::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $success = $item->delete();

        if ($success) {
            return response()->json(['message' => 'Pengaturan berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Gagal menghapus data'], 500);
        }
    }
}
