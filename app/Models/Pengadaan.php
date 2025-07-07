<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengadaan extends Model
{
    protected $table = 'pengadaan'; // 👈 beri tahu nama tabel aslinya

    protected $fillable = [
        'nama_suplier',
        'nama_perusahaan',
        'jenis_bank',
        'no_rekening',
        'atasnama_rekening',
        'no_preorder',
        'tanggal_pengadaan',
        'tanggal_pengajuan',
        'jenis_pengadaan_barang',
        'kuantum',
        'in_data',
        'jumlah_pembayaran',
        'spp',
        'user_id',
        'harga_sebelum_pajak', 
        'dpp', 
        'ppn_total', 
        'pph_total', 
        'nominal'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
