<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetRequest extends Model
{
    
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    protected $fillable = ['admin_id', 'status'];

}
