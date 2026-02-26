<?php

namespace Database\Seeders;

use App\Models\Penyewa;
use App\Models\TempatKos;
use Illuminate\Database\Seeder;

class TempatKosSeeder extends Seeder
{
    public function run(): void
    {
        $penyewas = Penyewa::all();

        $rooms = [
            [
                'lokasi'      => 'Malang',
                'nomor_kamar' => 'A01',
                'kode_unik'   => 'MLG-001',
                'id_penyewa'  => $penyewas[0]->id,
                'harga'       => 800000,
                'status'      => 'Ditempati',
            ],
            [
                'lokasi'      => 'Surabaya',
                'nomor_kamar' => 'B01',
                'kode_unik'   => 'SBY-001',
                'id_penyewa'  => $penyewas[1]->id,
                'harga'       => 1000000,
                'status'      => 'Ditempati',
            ],
            [
                'lokasi'      => 'Kediri',
                'nomor_kamar' => 'C01',
                'kode_unik'   => 'KDR-001',
                'id_penyewa'  => $penyewas[2]->id,
                'harga'       => 600000,
                'status'      => 'Ditempati',
            ],
            [
                'lokasi'      => 'Malang',
                'nomor_kamar' => 'A02',
                'kode_unik'   => 'MLG-002',
                'id_penyewa'  => null,
                'harga'       => 800000,
                'status'      => 'Kosong',
            ],
            [
                'lokasi'      => 'Surabaya',
                'nomor_kamar' => 'B02',
                'kode_unik'   => 'SBY-002',
                'id_penyewa'  => null,
                'harga'       => 900000,
                'status'      => 'Kosong',
            ],
        ];

        foreach ($rooms as $data) {
            TempatKos::create($data);
        }
    }
}
