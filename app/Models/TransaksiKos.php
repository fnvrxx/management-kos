<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKos extends Model
{
    protected $table = 'transaksi_kos';
    protected $guarded = [];
    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
    ];

    // Relationship back to room
    public function tempatKos()
    {
        return $this->hasOne(TempatKos::class, 'id_transaksi');
    }
    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa');
    }
}
