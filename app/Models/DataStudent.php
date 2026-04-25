<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class DataStudent extends Model
{
    use HasApiTokens;
    
    protected $guarded = ['id'];

    public function pembayaran_kas() {
        return $this->hasMany(PembayaranKas::class, 'datastudent_id', 'id ');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRoleAttribute()
    {
        return 'student';
    }
}
