<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    protected $table = 'penyewa';
    protected $guarded = [];
    protected $casts = [
        'rencana_lama_kos' => 'date',
        'start_date'       => 'date',
        'end_date'         => 'date',
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
    public function transaksis()
    {
        return $this->hasMany(TransaksiKos::class, 'id_penyewa');
    }
}
