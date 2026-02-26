<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempatKos extends Model
{
    protected $table = 'tempat_kos';
    protected $guarded = [];
    protected $casts = [
        'tgl_jatuh_tempo' => 'date',
    ];

    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa');
    }

    // One room has many transactions over time
    public function transaksis()
    {
        return $this->hasMany(TransaksiKos::class, 'id_tempat_kos');
    }

    // Latest transaction only
    public function latestTransaksi()
    {
        return $this->hasOne(TransaksiKos::class, 'id_tempat_kos')->latestOfMany('periode_selesai');
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-derive status based on id_penyewa
        static::saving(function ($model) {
            $model->status = $model->id_penyewa ? 'Ditempati' : 'Kosong';
        });
    }
}
