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
        $budi  = Penyewa::where('nama_lengkap', 'Budi Santoso')->first();
        $siti  = Penyewa::where('nama_lengkap', 'Siti Rahayu')->first();
        $ahmad = Penyewa::where('nama_lengkap', 'Ahmad Fauzi')->first();

        $mlg001 = TempatKos::where('lokasi', 'Malang')->where('nomor_kamar', 'A01')->first();
        $sby001 = TempatKos::where('lokasi', 'Surabaya')->where('nomor_kamar', 'B01')->first();
        $kdr001 = TempatKos::where('lokasi', 'Kediri')->where('nomor_kamar', 'C01')->first();

        // ========================================
        // BUDI — LUNAS (3 bulan dibayar penuh)
        // start_date: 2025-12-06, harga: 800k
        // Total: 3 × 800k = 2.4M → tgl_jatuh_tempo = 2025-12-06 + 3 = 2026-03-06
        // Today (Feb 26) < 2026-03-06 → LUNAS ✅
        // ========================================
        TransaksiKos::create([
            'id_penyewa'           => $budi->id,
            'id_tempat_kos'        => $mlg001->id,
            'tanggal_pembayaran'   => '2025-12-06',
            'nominal'              => 800000,
            'durasi_bulan_dibayar' => 1,
            'metode_pembayaran'    => 'Transfer',
            'periode_mulai'        => '2025-12-06',
            'periode_selesai'      => '2026-01-05',
            'history_pembayaran'   => 'Bayar 1 bulan (Des 2025)',
        ]);

        TransaksiKos::create([
            'id_penyewa'           => $budi->id,
            'id_tempat_kos'        => $mlg001->id,
            'tanggal_pembayaran'   => '2026-01-05',
            'nominal'              => 800000,
            'durasi_bulan_dibayar' => 1,
            'metode_pembayaran'    => 'Transfer',
            'periode_mulai'        => '2026-01-06',
            'periode_selesai'      => '2026-02-05',
            'history_pembayaran'   => 'Bayar 1 bulan (Jan 2026)',
        ]);

        TransaksiKos::create([
            'id_penyewa'           => $budi->id,
            'id_tempat_kos'        => $mlg001->id,
            'tanggal_pembayaran'   => '2026-02-05',
            'nominal'              => 800000,
            'durasi_bulan_dibayar' => 1,
            'metode_pembayaran'    => 'Tunai',
            'periode_mulai'        => '2026-02-06',
            'periode_selesai'      => '2026-03-05',
            'history_pembayaran'   => 'Bayar 1 bulan (Feb 2026)',
        ]);

        // ========================================
        // SITI — CICILAN (1 bulan lunas + bayar 500k dari 1M)
        // start_date: 2026-01-10, harga: 1M
        // Total: 1M + 500k = 1.5M → floor(1.5M/1M) = 1 month
        // tgl_jatuh_tempo = 2026-01-10 + 1 = 2026-02-10
        // Today (Feb 26) > 2026-02-10, partial = 1.5M % 1M = 500k
        // → CICILAN Rp 500.000 / 1.000.000 ✅
        // ========================================
        TransaksiKos::create([
            'id_penyewa'           => $siti->id,
            'id_tempat_kos'        => $sby001->id,
            'tanggal_pembayaran'   => '2026-01-10',
            'nominal'              => 1000000,
            'durasi_bulan_dibayar' => 1,
            'metode_pembayaran'    => 'Transfer',
            'periode_mulai'        => '2026-01-10',
            'periode_selesai'      => '2026-02-09',
            'history_pembayaran'   => 'Bayar 1 bulan (Jan 2026)',
        ]);

        TransaksiKos::create([
            'id_penyewa'           => $siti->id,
            'id_tempat_kos'        => $sby001->id,
            'tanggal_pembayaran'   => '2026-02-15',
            'nominal'              => 500000,
            'durasi_bulan_dibayar' => 0,
            'metode_pembayaran'    => 'QRIS',
            'periode_mulai'        => '2026-02-15',
            'periode_selesai'      => '2026-02-15',
            'history_pembayaran'   => 'Cicilan Rp 500.000 (Feb 2026)',
        ]);

        // ========================================
        // AHMAD — TUNGGAKAN (1 bulan lunas, belum bayar bulan ini)
        // start_date: 2026-01-15, harga: 600k
        // Total: 600k → floor(600k/600k) = 1 month
        // tgl_jatuh_tempo = 2026-01-15 + 1 = 2026-02-15
        // Today (Feb 26) > 2026-02-15, partial = 0
        // → TUNGGAKAN ✅
        // ========================================
        TransaksiKos::create([
            'id_penyewa'           => $ahmad->id,
            'id_tempat_kos'        => $kdr001->id,
            'tanggal_pembayaran'   => '2026-01-15',
            'nominal'              => 600000,
            'durasi_bulan_dibayar' => 1,
            'metode_pembayaran'    => 'Tunai',
            'periode_mulai'        => '2026-01-15',
            'periode_selesai'      => '2026-02-14',
            'history_pembayaran'   => 'Bayar 1 bulan (Jan 2026)',
        ]);
    }
}
