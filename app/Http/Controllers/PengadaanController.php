<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengadaanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nama_suplier' => 'required|string',
            'nama_perusahaan' => 'required|string',
            'jenis_bank' => 'required|in:mandiri,bca,bri',
            'no_rekening' => 'required|string',
            'no_preorder' => ['required', 'regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'required|date',
            'jenis_pengadaan_barang' => 'required|in:beras,gabah',
            'kuantum' => 'required|string',
            'in_data' => 'nullable|array',
            'in_data.*.no_in' => 'nullable|numeric',
            'in_data.*.tanggal_in' => 'nullable|date',
            'in_data.*.kuantum_in' => 'nullable|string',
            'jumlah_pembayaran' => 'required|string',
            'spp' => 'required|integer',
        ]);

        $pengadaan = new Pengadaan();
        $pengadaan->nama_suplier = $request->nama_suplier;
        $pengadaan->nama_perusahaan = $request->nama_perusahaan;
        $pengadaan->jenis_bank = $request->jenis_bank;
        $pengadaan->no_rekening = $request->no_rekening;
        $pengadaan->no_preorder = $request->no_preorder;
        $pengadaan->tanggal_pengadaan = $request->tanggal_pengadaan;
        $pengadaan->jenis_pengadaan_barang = $request->jenis_pengadaan_barang;
        $pengadaan->kuantum = $request->kuantum;
        $pengadaan->in_data = json_encode($request->in_data);
        $pengadaan->jumlah_pembayaran = $request->jumlah_pembayaran;
        $pengadaan->spp = $request->spp;
        $pengadaan->user_id = auth()->id();
        $pengadaan->save();

        return response()->json(['message' => 'Data berhasil disimpan']);
    }

    public function getSuplierData($nama)
    {
        $data = Pengadaan::where('nama_suplier', $nama)->first();
        return response()->json($data);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('search');
        $bulan = $request->query('bulan');

        $query = Pengadaan::with('user');

        if ($user->role === 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_pengadaan_barang', 'like', '%' . $search . '%')
                  ->orWhere('no_preorder', 'like', '%' . $search . '%')
                  ->orWhere('nama_suplier', 'like', '%' . $search . '%')
                  ->orWhere('nama_perusahaan', 'like', '%' . $search . '%');
            });
        }

        if ($bulan) {
            try {
                [$year, $month] = explode('-', $bulan);
                $query->whereYear('tanggal_pengadaan', $year)
                    ->whereMonth('tanggal_pengadaan', $month);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Format bulan tidak valid. Gunakan format YYYY-MM.'], 422);
            }
        }

        $pengadaan = $query->latest()->get();

        return response()->json($pengadaan);
    }

    public function show($id)
    {
        $pengadaan = Pengadaan::with('user')->findOrFail($id);
        $this->authorizeAccess($pengadaan);

        return response()->json($pengadaan);
    }

    public function update(Request $request, $id)
    {
        $pengadaan = Pengadaan::findOrFail($id);
        $this->authorizeAccess($pengadaan);

        $request->validate([
            'nama_suplier' => 'sometimes|string',
            'nama_perusahaan' => 'sometimes|string',
            'jenis_bank' => 'sometimes|in:mandiri,bca,bri',
            'no_rekening' => 'sometimes|string',
            'no_preorder' => ['sometimes', 'regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'sometimes|date',
            'jenis_pengadaan_barang' => 'sometimes|in:beras,gabah',
            'kuantum' => 'sometimes|string',
            'in_data' => 'nullable|array',
            'in_data.*.no_in' => 'nullable|numeric',
            'in_data.*.tanggal_in' => 'nullable|date',
            'in_data.*.kuantum_in' => 'nullable|string',
            'jumlah_pembayaran' => 'sometimes|string',
            'spp' => 'sometimes|integer',
        ]);

        // Pastikan `in_data` tetap dalam bentuk JSON saat disimpan
        $data = $request->all();
        if ($request->has('in_data')) {
            $data['in_data'] = json_encode($request->in_data);
        }

        $pengadaan->update($data);

        return response()->json(['message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $pengadaan = Pengadaan::findOrFail($id);
        $this->authorizeAccess($pengadaan);
        $pengadaan->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    public function download($id)
    {
        $pengadaan = Pengadaan::findOrFail($id);
        $this->authorizeAccess($pengadaan);

        $content = json_encode($pengadaan, JSON_PRETTY_PRINT);
        $filename = 'pengadaan_' . $pengadaan->id . '.json';

        return response($content, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    protected function authorizeAccess(Pengadaan $pengadaan)
    {
        $user = Auth::user();

        if ($user->role === 'admin' && $pengadaan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
