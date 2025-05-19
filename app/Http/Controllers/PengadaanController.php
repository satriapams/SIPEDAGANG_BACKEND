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

            // Validasi dengan format 1234/12/11C30/2024
            'no_preorder' => ['required', 'regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'required|date',
            'jenis_pengadaan_barang' => 'required|in:beras,gabah',
            'kuantum' => 'required|string',

            'in_data' => 'nullable|array', // array dari no_in, tanggal_in, kuantum_in
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

        $pengadaan->in_data = json_encode($request->in_data); // disimpan sebagai JSON array

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

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'superadmin') {
            $pengadaan = Pengadaan::with('user')->get(); // semua data
        } else {
            $pengadaan = Pengadaan::with('user')->where('user_id', $user->id)->get(); // hanya milik sendiri
        }

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
            'nama_suplier' => 'string',
            'nama_perusahaan' => 'string',
            'jenis_bank' => 'in:mandiri,bca,bri',
            'no_rekening' => 'string',
            'no_preorder' => ['regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'date',
            'jenis_pengadaan_barang' => 'in:beras,gabah',
            'kuantum' => 'string',
            'in_data' => 'nullable|array',
            'jumlah_pembayaran' => 'string',
            'spp' => 'integer',
        ]);

        $pengadaan->update($request->all());

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
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    protected function authorizeAccess(Pengadaan $pengadaan)
    {
        $user = Auth::user();

        if ($user->role === 'admin' && $pengadaan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }
}