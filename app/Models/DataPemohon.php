<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPemohon extends Model
{
    protected $fillable = [
        'nama_suplier',
        'nama_perusahaan',
        'jenis_bank',
        'no_rekening',
        'atasnama_rekening',
    ];
}

