<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempatKos extends Model
{
    protected $table = 'tempat_kos';
    protected $guarded = [];

    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa');
    }

    public function transaksi()
    {
        return $this->belongsTo(TransaksiKos::class, 'id_transaksi');
    }

    // AUTO-GENERATE UNIQUE CODE LOGIC
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $prefix = match ($model->lokasi) {
                'Malang' => 'MLG',
                'Surabaya' => 'SBY',
                'Kediri' => 'KDR',
                default => 'GEN',
            };

            // Count existing rooms in this location to increment
            $count = static::where('lokasi', $model->lokasi)->count() + 1;

            // Pad with zeros (e.g., 001)
            $model->kode_unik = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        });
    }
}
