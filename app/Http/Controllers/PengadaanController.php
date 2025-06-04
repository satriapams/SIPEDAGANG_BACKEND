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
            'jenis_bank' => 'required|in:MANDIRI,BCA,BRI,BANK JATENG,BNI',
            'no_rekening' => 'required|string',
            'no_preorder' => ['required', 'regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'required|date',
            'jenis_pengadaan_barang' => 'required|string',
            'kuantum' => ['required', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'in_data' => 'nullable|array',
            'in_data.*.no_in' => 'nullable|numeric',
            'in_data.*.tanggal_in' => 'nullable|date',
            'in_data.*.kuantum_in' => ['nullable', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'jumlah_pembayaran' => ['required', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'spp' => 'required|integer',
        ]);

        $existing = Pengadaan::where('no_preorder', $request->no_preorder)->first();

        if ($existing) {
            if (
                $existing->nama_suplier === $request->nama_suplier &&
                $existing->nama_perusahaan === $request->nama_perusahaan &&
                strtoupper($existing->jenis_pengadaan_barang) === strtoupper($request->jenis_pengadaan_barang)
            ) {
                $existing->kuantum = $this->jumlahkanKuantum($existing->kuantum, $request->kuantum);
                $existing->save();

                return response()->json(['message' => 'Data berhasil diperbarui dengan penambahan kuantum.'], 200);
            } else {
                return response()->json(['message' => 'Gagal menambahkan: no_preorder sudah digunakan oleh data dengan suplier/perusahaan/barang yang berbeda.'], 409);
            }
        }

        $pengadaan = new Pengadaan();
        $pengadaan->nama_suplier = $request->nama_suplier;
        $pengadaan->nama_perusahaan = $request->nama_perusahaan;
        $pengadaan->jenis_bank = strtoupper($request->jenis_bank);
        $pengadaan->no_rekening = $request->no_rekening;
        $pengadaan->no_preorder = $request->no_preorder;
        $pengadaan->tanggal_pengadaan = $request->tanggal_pengadaan;

        $allowedJenis = ['BERAS', 'GABAH', 'MINYAK'];
        $inputJenis = strtoupper($request->jenis_pengadaan_barang);
        $pengadaan->jenis_pengadaan_barang = in_array($inputJenis, $allowedJenis) ? $inputJenis : $inputJenis;

        $pengadaan->kuantum = strtoupper($request->kuantum);
        $pengadaan->in_data = json_encode($request->in_data);
        $pengadaan->jumlah_pembayaran = strtoupper($request->jumlah_pembayaran);
        $pengadaan->spp = $request->spp;
        $pengadaan->user_id = auth()->id();
        $pengadaan->save();

        return response()->json(['message' => 'Data berhasil disimpan'], 201);
    }

    private function jumlahkanKuantum($kuantumLama, $kuantumBaru)
    {
        preg_match('/([\d.]+)\s*(KG|LITER|PCS)/i', $kuantumLama, $matchLama);
        preg_match('/([\d.]+)\s*(KG|LITER|PCS)/i', $kuantumBaru, $matchBaru);

        if (!$matchLama || !$matchBaru || strtoupper($matchLama[2]) !== strtoupper($matchBaru[2])) {
            return $kuantumBaru;
        }

        $total = (float)$matchLama[1] + (float)$matchBaru[1];
        return $total . ' ' . strtoupper($matchLama[2]);
    }

    public function getSuplierData($nama)
    {
        $data = Pengadaan::where('nama_suplier', $nama)
            ->latest('created_at')
            ->first(['nama_perusahaan', 'jenis_bank', 'no_rekening']);

        if (!$data) {
            return response()->json(['message' => 'Data suplier tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $perPage = $request->query('per_page', 10);
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

        $pengadaan = $query->orderByDesc('tanggal_pengadaan')->paginate($perPage);

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
            'jenis_bank' => 'sometimes|in:MANDIRI,BCA,BRI,BANK JATENG,BNI',
            'no_rekening' => 'sometimes|string',
            'no_preorder' => ['sometimes', 'regex:/^\d{4}\/\d{2}\/[A-Za-z0-9]+\/\d{4}$/'],
            'tanggal_pengadaan' => 'sometimes|date',
            'jenis_pengadaan_barang' => 'sometimes|string',
            'kuantum' => ['sometimes', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'in_data' => 'nullable|array',
            'in_data.*.no_in' => 'nullable|numeric',
            'in_data.*.tanggal_in' => 'nullable|date',
            'in_data.*.kuantum_in' => ['nullable', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'jumlah_pembayaran' => ['sometimes', 'regex:/^\d+\s?(KG|LITER|PCS)$/i'],
            'spp' => 'sometimes|integer',
        ]);

        $data = $request->all();

        if ($request->has('jenis_bank')) {
            $data['jenis_bank'] = strtoupper($request->jenis_bank);
        }

        if ($request->filled('jenis_pengadaan_barang')) {
            $allowedJenis = ['BERAS', 'GABAH', 'MINYAK'];
            $inputJenis = strtoupper($request->jenis_pengadaan_barang);
            $data['jenis_pengadaan_barang'] = in_array($inputJenis, $allowedJenis) ? $inputJenis : $inputJenis;
        }

        if ($request->has('kuantum')) {
            $data['kuantum'] = strtoupper($request->kuantum);
        }

        if ($request->has('jumlah_pembayaran')) {
            $data['jumlah_pembayaran'] = strtoupper($request->jumlah_pembayaran);
        }

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
