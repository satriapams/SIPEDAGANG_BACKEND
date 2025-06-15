<?php

namespace App\Http\Controllers;

use App\Models\DataPemohon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DataPemohonController extends Controller
{
    // Daftar bank yang diizinkan
    protected $allowedBanks = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];

    // Ambil semua data pemohon dengan paginasi & pencarian
    public function index(Request $request)
    {
        $query = DataPemohon::query();

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_perusahaan', 'like', "%$search%")
                  ->orWhere('nama_suplier', 'like', "%$search%");
            });
        }

        $data = $query->paginate(10);
        return response()->json($data);
    }

    public function show($id)
    {
        $pemohon = DataPemohon::find($id);

        if (!$pemohon) {
            return response()->json([
                'message' => 'Data Pemohon tidak ditemukan'
            ], 404);
        }

        return response()->json($pemohon);
    }

    // Simpan data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_suplier' => 'required|string',
            'nama_perusahaan' => 'required|string|unique:data_pemohons,nama_perusahaan',
            'jenis_bank' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    $allowed = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
                    if (!in_array(strtoupper($value), $allowed) && strlen($value) < 3) {
                        $fail("Bank tidak valid atau terlalu pendek.");
                    }
                }
            ],
            'no_rekening' => 'required|string',
            'atasnama_rekening' => 'required|string',
        ]);

        $validated['jenis_bank'] = strtoupper($validated['jenis_bank']);

        $pemohon = DataPemohon::create($validated);

        return response()->json([
            'message' => 'Data Pemohon berhasil ditambahkan',
            'data' => $pemohon
        ], 201);
    }

    // Update data pemohon
    public function update(Request $request, $id)
    {
        $pemohon = DataPemohon::find($id);

        if (!$pemohon) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama_suplier' => 'required|string',
            'nama_perusahaan' => 'required|string',
            'jenis_bank' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    $allowed = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
                    if (!in_array(strtoupper($value), $allowed) && strlen($value) < 3) {
                        $fail("Bank tidak valid atau terlalu pendek.");
                    }
                }
            ],
            'no_rekening' => 'required|string',
            'atasnama_rekening' => 'required|string',
        ]);

        $validated['jenis_bank'] = strtoupper($validated['jenis_bank']);

        $pemohon->update($validated);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $pemohon
        ]);
    }

    // Hapus data pemohon
    public function destroy(Request $request, $id)
    {
        $pemohon = DataPemohon::find($id);

        if (!$pemohon) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $pemohon->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    // Ambil data lengkap berdasarkan nama_perusahaan PERSIS
    public function getByPerusahaan($nama_perusahaan)
    {
        $pemohon = DataPemohon::where('nama_perusahaan', $nama_perusahaan)->first();

        if (!$pemohon) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($pemohon);
    }

    // 🔍 AUTOCOMPLETE — Ambil list perusahaan mirip untuk suggestion
    public function getByNamaPerusahaan(Request $request)
    {
        $namaPerusahaan = $request->query('nama_perusahaan');

        if (!$namaPerusahaan) {
            return response()->json([], 200);
        }

        $data = DataPemohon::where('nama_perusahaan', 'like', '%' . $namaPerusahaan . '%')
            ->limit(5)
            ->get([
                'nama_perusahaan', 'nama_suplier', 'jenis_bank', 'no_rekening', 'atasnama_rekening'
            ]);

        return response()->json($data);
    }

    // Import dari Excel
    public function uploadExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $headerMap = [
            'B' => 'nama_perusahaan',
            'C' => 'nama_suplier',
            'D' => 'jenis_bank',
            'E' => 'atasnama_rekening',
            'F' => 'no_rekening',
        ];

        foreach ($rows as $index => $row) {
            if ($index === 1) continue; // skip header

            $data = [];
            foreach ($headerMap as $col => $field) {
                $value = $row[$col] ?? null;
                $data[$field] = is_string($value) ? strtoupper(trim($value)) : $value;
            }

            $validator = Validator::make($data, [
                'nama_suplier' => 'required|string',
                'nama_perusahaan' => 'required|string|unique:data_pemohons,nama_perusahaan',
                'jenis_bank' => [
                    'required', 'string',
                    function ($attribute, $value, $fail) {
                        $allowed = ['MANDIRI', 'BCA', 'BRI', 'BANK JATENG', 'BNI'];
                        if (!in_array(strtoupper($value), $allowed) && strlen($value) < 3) {
                            $fail("Bank tidak valid atau terlalu pendek.");
                        }
                    }
                ],
                'no_rekening' => 'required|string',
                'atasnama_rekening' => 'required|string',
            ]);

            if ($validator->fails()) {
                continue;
            }

            DataPemohon::create($data);
        }

        return response()->json(['message' => 'Excel berhasil diimpor dan dimasukkan ke database']);
    }
}
