<?php

namespace Database\Seeders;

use App\Models\Penyewa;
use App\Models\Reminder;
use App\Models\TempatKos;
use App\Models\TransaksiKos;
use Illuminate\Database\Seeder;

class ReminderSeeder extends Seeder
{
    public function run(): void
    {
        $siti  = Penyewa::where('nama_lengkap', 'Siti Rahayu')->first();
        $ahmad = Penyewa::where('nama_lengkap', 'Ahmad Fauzi')->first();

        $kamarSiti  = $siti->tempatKos;
        $kamarAhmad = $ahmad->tempatKos;

        // Siti — cicilan, sisa 500k
        Reminder::create([
            'id_penyewa'       => $siti->id,
            'end_date'         => $kamarSiti?->tgl_jatuh_tempo ?? '2026-02-10',
            'tanggungan'       => $kamarSiti ? TransaksiKos::getTanggungan($kamarSiti) : 500000,
            'broadcast'        => false,
            'history_reminder' => null,
        ]);

        // Ahmad — tunggakan, sisa 600k (full month)
        Reminder::create([
            'id_penyewa'       => $ahmad->id,
            'end_date'         => $kamarAhmad?->tgl_jatuh_tempo ?? '2026-02-15',
            'tanggungan'       => $kamarAhmad ? TransaksiKos::getTanggungan($kamarAhmad) : 600000,
            'broadcast'        => false,
            'history_reminder' => null,
        ]);
    }
}
