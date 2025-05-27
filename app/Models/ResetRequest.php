<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetRequest extends Model
{
    protected $fillable = ['admin_id', 'status'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
    