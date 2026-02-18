<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    protected $table = 'penyewa';
    protected $guarded = [];
    protected $casts = [
        'rencana_lama_kos' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'tgl_jatuh_tempo_berikutnya' => 'date', // <--- Tambahkan ini
    ];

    // Relationship: A tenant has one room assignment currently
    public function tempatKos()
    {
        return $this->hasOne(TempatKos::class, 'id_penyewa');
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class, 'id_penyewa');
    }
    public function riwayatTransaksi()
    {
        return $this->hasManyThrough(
            TransaksiKos::class,
            TempatKos::class,
            'id_penyewa', // Foreign key on tempat_kos table...
            'id', // Foreign key on transaksi_kos table...
            'id', // Local key on penyewa table...
            'id_transaksi' // Local key on tempat_kos table...
        );
    }
}
