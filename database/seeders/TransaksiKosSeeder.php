<?php

namespace Database\Seeders;

use App\Models\Penyewa;
use App\Models\TempatKos;
use App\Models\TransaksiKos;
use Illuminate\Database\Seeder;

class TransaksiKosSeeder extends Seeder
{
    public function run(): void
    {
        $penyewas = Penyewa::all();

        // Fetch rooms by kode_unik
        $mlg001 = TempatKos::where('kode_unik', 'MLG-001')->first();
        $sby001 = TempatKos::where('kode_unik', 'SBY-001')->first();
        $kdr001 = TempatKos::where('kode_unik', 'KDR-001')->first();

        $transaksis = [
            [
                'id_penyewa'           => $penyewas[0]->id,
                'id_tempat_kos'        => $mlg001->id,
                'tanggal_pembayaran'   => '2026-02-01',
                'nominal'              => 800000,
                'durasi_bulan_dibayar' => 1,
                'metode_pembayaran'    => 'Transfer',
                'bukti_transfer'       => null,
                'periode_mulai'        => '2026-02-01',
                'periode_selesai'      => '2026-02-28',
                'history_pembayaran'   => 'Pembayaran bulan Februari 2026',
            ],
            [
                'id_penyewa'           => $penyewas[1]->id,
                'id_tempat_kos'        => $sby001->id,
                'tanggal_pembayaran'   => '2026-02-03',
                'nominal'              => 1000000,
                'durasi_bulan_dibayar' => 1,
                'metode_pembayaran'    => 'Tunai',
                'bukti_transfer'       => null,
                'periode_mulai'        => '2026-02-03',
                'periode_selesai'      => '2026-03-03',
                'history_pembayaran'   => 'Pembayaran bulan Februari 2026',
            ],
            [
                'id_penyewa'           => $penyewas[2]->id,
                'id_tempat_kos'        => $kdr001->id,
                'tanggal_pembayaran'   => '2026-02-15',
                'nominal'              => 1200000,
                'durasi_bulan_dibayar' => 2,
                'metode_pembayaran'    => 'Transfer',
                'bukti_transfer'       => null,
                'periode_mulai'        => '2026-02-15',
                'periode_selesai'      => '2026-04-15',
                'history_pembayaran'   => 'Pembayaran 2 bulan (Feb-Mar 2026)',
            ],
        ];

        foreach ($transaksis as $data) {
            // TransaksiKos::boot() saved hook will auto-sync tgl_jatuh_tempo on tempat_kos
            TransaksiKos::create($data);
        }
    }
}
