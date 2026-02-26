<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKos extends Model
{
    protected $table = 'transaksi_kos';
    protected $guarded = [];
    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'periode_mulai'      => 'date',
        'periode_selesai'    => 'date',
    ];

    public function tempatKos()
    {
        return $this->belongsTo(TempatKos::class, 'id_tempat_kos');
    }

    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa');
    }

    // After saving a transaction, sync tgl_jatuh_tempo on the room
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($transaksi) {
            if ($transaksi->id_tempat_kos && $transaksi->periode_selesai) {
                $kamar = TempatKos::find($transaksi->id_tempat_kos);
                if ($kamar) {
                    // Only update if this transaction's periode_selesai is the latest
                    $latest = static::where('id_tempat_kos', $transaksi->id_tempat_kos)
                        ->max('periode_selesai');

                    $kamar->tgl_jatuh_tempo = $latest;
                    $kamar->saveQuietly();
                }
            }
        });
    }
}
