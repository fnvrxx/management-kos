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

        // AUTO-GENERATE UNIQUE CODE on create
        static::creating(function ($model) {
            $prefix = match ($model->lokasi) {
                'Malang'   => 'MLG',
                'Surabaya' => 'SBY',
                'Kediri'   => 'KDR',
                default    => 'GEN',
            };

            $count = static::where('lokasi', $model->lokasi)->count() + 1;
            $model->kode_unik = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        });
    }
}
