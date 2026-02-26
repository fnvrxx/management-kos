<?php

namespace Database\Seeders;

use App\Models\Pengeluaran;
use Illuminate\Database\Seeder;

class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $pengeluarans = [
            [
                'judul'     => 'Token Listrik',
                'nominal'   => 150000,
                'tanggal'   => '2026-02-01',
                'kategori'  => 'Operasional',
                'bukti_foto' => null,
                'keterangan' => 'Token listrik semua kamar bulan Februari',
            ],
            [
                'judul'     => 'Sedot WC',
                'nominal'   => 300000,
                'tanggal'   => '2026-02-10',
                'kategori'  => 'Perbaikan',
                'bukti_foto' => null,
                'keterangan' => 'Sedot WC kamar mandi lantai 1',
            ],
            [
                'judul'     => 'Air PDAM',
                'nominal'   => 80000,
                'tanggal'   => '2026-02-15',
                'kategori'  => 'Operasional',
                'bukti_foto' => null,
                'keterangan' => 'Tagihan air bulan Februari',
            ],
            [
                'judul'     => 'Perbaikan Pintu Kamar 3',
                'nominal'   => 200000,
                'tanggal'   => '2026-02-18',
                'kategori'  => 'Perbaikan',
                'bukti_foto' => null,
                'keterangan' => 'Ganti engsel dan kunci pintu kamar 3',
            ],
        ];

        foreach ($pengeluarans as $data) {
            Pengeluaran::create($data);
        }
    }
}
