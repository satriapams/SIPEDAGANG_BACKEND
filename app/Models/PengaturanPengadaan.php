<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanPengadaan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_pengadaans';

    protected $fillable = [
        'jenis_pengadaan_barang',
        'satuan',
        'harga_per_satuan',
        'ppn',
        'pph',
        'tanpa_pajak',
    ];
}
