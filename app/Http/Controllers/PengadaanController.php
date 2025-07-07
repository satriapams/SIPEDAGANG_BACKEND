<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\PengaturanPengadaanController;
use App\Http\Controllers\DataPemohonController;
use App\Models\PengaturanPengadaan;
use App\Models\DataPemohon;


class PengadaanController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama_suplier' => 'required|string',
                'nama_perusahaan' => 'required|string',
                'jenis_bank' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $allowed = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
                        if (!in_array(strtoupper($value), $allowed) && strlen($value) < 3) {
                            $fail("Bank tidak valid atau terlalu pendek.");
                        }
                    },
                ],
                'no_rekening' => 'required|string',
                'atasnama_rekening' => 'required|string',
                'no_preorder' => 'required|string',
                'tanggal_pengadaan' => 'required|date',
                'tanggal_pengajuan' => 'required|date',
                'jenis_pengadaan_barang' => 'required|string',
                'kuantum' => ['required', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
                'in_data' => 'nullable|array',
                'in_data.*.no_in' => 'nullable|numeric',
                'in_data.*.tanggal_in' => 'nullable|date',
                'in_data.*.kuantum_in' => ['nullable', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
                'spp' => ['nullable', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }

        $jumlahPembayaran = '';
        $totalJumlah = 0;
        $satuan = '';

        if ($request->has('in_data') && is_array($request->in_data)) {
            foreach ($request->in_data as $item) {
                if (isset($item['kuantum_in'])) {
                    preg_match('/([\d.]+)\s*(KG|LITER|PCS)/i', $item['kuantum_in'], $match);
                    if ($match) {
                        $totalJumlah += (float)$match[1];
                        $satuan = strtoupper($match[2]);
                    }
                }
            }
            if ($totalJumlah > 0 && $satuan) {
                $jumlahPembayaran = $totalJumlah . ' ' . $satuan;
            }
        }

        $existing = Pengadaan::where('no_preorder', $request->no_preorder)->first();

        if ($existing) {
            if (
                $existing->nama_suplier === $request->nama_suplier &&
                $existing->nama_perusahaan === $request->nama_perusahaan &&
                strtoupper($existing->jenis_pengadaan_barang) === strtoupper($request->jenis_pengadaan_barang)
            ) {
                $existing->kuantum = $this->jumlahkanKuantum($existing->kuantum, $request->kuantum);
                $existingInData = $existing->in_data ? json_decode($existing->in_data, true) : [];
                $incomingInData = $request->in_data ?? [];

                if (is_array($incomingInData)) {
                    $mergedInData = array_merge($existingInData, $incomingInData);
                    $existing->in_data = json_encode($mergedInData);
                    $existing->jumlah_pembayaran = $this->hitungJumlahPembayaran($mergedInData);
                }

                $existing->save();

                return response()->json(['message' => 'Data berhasil diperbarui dengan penambahan kuantum dan in_data.'], 200);
            } else {
                return response()->json([
                    'message' => 'Gagal menambahkan: no_preorder sudah digunakan oleh data dengan suplier/perusahaan/barang yang berbeda.'
                ], 409);
            }
        }

        $pengaturan = PengaturanPengadaan::where('jenis_pengadaan_barang', strtoupper($request->jenis_pengadaan_barang))->first();
        if (!$pengaturan) {
            return response()->json([
                'message' => 'Jenis pengadaan barang belum terdaftar di pengaturan. Silakan tambahkan terlebih dahulu.'
            ], 400);
        }

        $pengadaan = new Pengadaan();
        $pengadaan->nama_suplier = $request->nama_suplier;
        $pengadaan->nama_perusahaan = $request->nama_perusahaan;
        $pengadaan->jenis_bank = strtoupper($request->jenis_bank);
        $pengadaan->no_rekening = $request->no_rekening;
        $pengadaan->atasnama_rekening = $request->atasnama_rekening;
        $pengadaan->no_preorder = $request->no_preorder;
        $pengadaan->tanggal_pengadaan = $request->tanggal_pengadaan;
        $pengadaan->tanggal_pengajuan = $request->tanggal_pengajuan;

        $allowedJenis = ['BERAS', 'GABAH', 'MINYAK'];
        $inputJenis = strtoupper($request->jenis_pengadaan_barang);
        $pengadaan->jenis_pengadaan_barang = in_array($inputJenis, $allowedJenis) ? $inputJenis : $inputJenis;

        $pengadaan->kuantum = strtoupper($request->kuantum);
        $pengadaan->in_data = json_encode($request->in_data);
        $pengadaan->jumlah_pembayaran = $jumlahPembayaran;
        $pengadaan->spp = $request->filled('spp') ? (string)$request->spp : '';
        $pengadaan->user_id = auth()->id();

        // Perhitungan pajak dan nominal
        
        if ($pengaturan) {
            preg_match('/([\d.]+)/', $jumlahPembayaran, $matches);
            $jumlah = isset($matches[1]) ? (float)$matches[1] : 0;

            if ($pengaturan->tanpa_pajak) {
                $pengadaan->nominal = round($jumlah * $pengaturan->harga_per_satuan, 2);
                $pengadaan->harga_sebelum_pajak = null;
                $pengadaan->dpp = null;
                $pengadaan->ppn_total = null;
                $pengadaan->pph_total = null;
            } else {
                $hargaSebelumPajak = $jumlah * $pengaturan->harga_per_satuan;
                $dpp = $hargaSebelumPajak * (100 / 111);
                $ppn = $dpp * ($pengaturan->ppn / 100);
                $pph = $dpp * ($pengaturan->pph / 100);
                $nominal = $dpp - $pph;

                $pengadaan->harga_sebelum_pajak = round($hargaSebelumPajak, 2);
                $pengadaan->dpp = round($dpp, 2);
                $pengadaan->ppn_total = round($ppn, 2);
                $pengadaan->pph_total = round($pph, 2);
                $pengadaan->nominal = round($nominal, 2);
            }
        }


        $pengadaan->save();

        return response()->json(['message' => 'Data berhasil disimpan'], 201);
    }

    private function hitungJumlahPembayaran(array $inData): string
    {
        $total = 0;
        $satuan = '';

        foreach ($inData as $item) {
            if (isset($item['kuantum_in'])) {
                // Pisahkan angka dan satuan
                if (preg_match('/^(\d+(?:\.\d+)?)\s*(KG|LITER|PCS)$/i', strtoupper($item['kuantum_in']), $match)) {
                    $total += (float)$match[1];
                    $satuan = strtoupper($match[2]); // Ambil satuan dari salah satu input
                }
            }
        }

        return $total > 0 ? $total . ' ' . $satuan : '';
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

    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('search');
        $bulan = $request->query('bulan');
        $tanggalAwal = $request->query('tanggal_awal'); // YYYY-MM-DD
        $tanggalAkhir = $request->query('tanggal_akhir'); // YYYY-MM-DD
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

        if ($tanggalAwal && $tanggalAkhir) {
            try {
                $query->whereBetween('tanggal_pengadaan', [$tanggalAwal, $tanggalAkhir]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.'], 422);
            }
        }

        $pengadaan = $query->orderByDesc('tanggal_pengadaan')->paginate($perPage);

        return response()->json($pengadaan);
    }

    public function clear()
    {
        $user = Auth::user();

        $query = Pengadaan::with('user');

        if ($user->role === 'admin') {
            $query->where('user_id', $user->id);
        }

        $pengadaan = $query->orderByDesc('tanggal_pengadaan')->get();

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

        try {
            $request->validate([
                'nama_suplier' => 'sometimes|required|string',
                'nama_perusahaan' => 'sometimes|required|string',
                'jenis_bank' => [
                    'sometimes',
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $allowed = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
                        if (!in_array(strtoupper($value), $allowed) && strlen($value) < 3) {
                            $fail("Bank tidak valid atau terlalu pendek.");
                        }
                    },
                ],
                'no_rekening' => 'sometimes|required|string',
                'atasnama_rekening' => 'sometimes|required|string',
                'no_preorder' => 'required|string',
                'tanggal_pengadaan' => 'sometimes|required|date',
                'tanggal_pengajuan' => 'sometimes|required|date',
                'jenis_pengadaan_barang' => 'sometimes|required|string',
                'kuantum' => ['sometimes', 'required', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
                'in_data' => 'nullable|array',
                'in_data.*.no_in' => 'nullable|numeric',
                'in_data.*.tanggal_in' => 'nullable|date',
                'in_data.*.kuantum_in' => ['nullable', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
                'spp' => ['nullable', 'regex:/^\d+(\.\d+)?\s*(KG|LITER|PCS)$/i'],
            ], [
                'nama_suplier.required' => 'Nama Supplier harus diisi',
                'nama_perusahaan.required' => 'Nama Perusahaan harus diisi',
                'jenis_bank.required' => 'Jenis Bank harus diisi',
                'no_rekening.required' => 'Nomor Rekening harus diisi',
                'atasnama_rekening.required' => 'Atas Nama Rekening harus diisi',
                'no_preorder.required' => 'Nomor PO harus diisi',
                'no_preorder.regex' => 'Format Nomor PO tidak valid',
                'tanggal_pengadaan.required' => 'Tanggal Pengadaan harus diisi',
                'tanggal_pengadaan.date' => 'Tanggal Pengadaan tidak valid',
                'tanggal_pengajuan.required' => 'Tanggal Pengajuan harus diisi',
                'tanggal_pengajuan.date' => 'Tanggal Pengajuan tidak valid',
                'jenis_pengadaan_barang.required' => 'Jenis Pengadaan harus diisi',
                'kuantum.required' => 'Kuantum harus diisi',
                'kuantum.regex' => 'Format kuantum tidak valid',
            ]);
        } catch (ValidationException $e) {
            $pesan = 'Validasi gagal: ';
            $pesan .= implode(', ', collect($e->errors())->flatten()->toArray());

            return response()->json([
                'message' => $pesan
            ], 422);
        }

        if ($request->filled('in_data')) {
            foreach ($request->in_data as $item) {
                if (empty($item['no_in']) || empty($item['tanggal_in']) || empty($item['kuantum_in'])) {
                    return response()->json([
                        'message' => 'Validasi gagal: Minimal satu Data IN harus diisi lengkap'
                    ], 422);
                }
            }
        }

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

        if ($request->has('in_data')) {
            $data['in_data'] = json_encode($request->in_data);
            $totalJumlah = 0;
            $satuan = '';
            foreach ($request->in_data as $item) {
                if (isset($item['kuantum_in'])) {
                    preg_match('/([\d.]+)\s*(KG|LITER|PCS)/i', $item['kuantum_in'], $match);
                    if ($match) {
                        $totalJumlah += (float)$match[1];
                        $satuan = strtoupper($match[2]);
                    }
                }
            }
            if ($totalJumlah > 0 && $satuan) {
                $data['jumlah_pembayaran'] = $totalJumlah . ' ' . $satuan;
            }
        }

        if ($request->has('spp')) {
            $data['spp'] = $request->filled('spp') ? (string)$request->spp : '';
        }

        $pengadaan->update($data);

        $pengaturan = PengaturanPengadaan::where('jenis_pengadaan_barang', $pengadaan->jenis_pengadaan_barang)->first();
        if (!$pengaturan) {
            return response()->json([
                'message' => 'Jenis pengadaan barang belum terdaftar di pengaturan. Tidak bisa menghitung nominal.'
            ], 400);
        }

        preg_match('/([\d.]+)/', $pengadaan->jumlah_pembayaran, $matches);
        $jumlah = isset($matches[1]) ? (float)$matches[1] : 0;

        if ($pengaturan->tanpa_pajak) {
            $pengadaan->update([
                'harga_sebelum_pajak' => null,
                'dpp' => null,
                'ppn_total' => null,
                'pph_total' => null,
                'nominal' => round($jumlah * $pengaturan->harga_per_satuan, 2)
            ]);
        } else {
            $hargaSebelumPajak = $jumlah * $pengaturan->harga_per_satuan;
            $dpp = $hargaSebelumPajak * (100 / 111);
            $ppn = $dpp * ($pengaturan->ppn / 100);
            $pph = $dpp * ($pengaturan->pph / 100);
            $nominal = $dpp - $pph;

            $pengadaan->update([
                'harga_sebelum_pajak' => round($hargaSebelumPajak, 2),
                'dpp' => round($dpp, 2),
                'ppn_total' => round($ppn, 2),
                'pph_total' => round($pph, 2),
                'nominal' => round($nominal, 2)
            ]);
        }

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
