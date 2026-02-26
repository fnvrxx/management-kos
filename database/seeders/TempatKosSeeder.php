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
                'id_penyewa'  => $penyewas[0]->id,
                'harga'       => 800000,
            ],
            [
                'lokasi'      => 'Surabaya',
                'nomor_kamar' => 'B01',
                'id_penyewa'  => $penyewas[1]->id,
                'harga'       => 1000000,
            ],
            [
                'lokasi'      => 'Kediri',
                'nomor_kamar' => 'C01',
                'id_penyewa'  => $penyewas[2]->id,
                'harga'       => 600000,
            ],
            [
                'lokasi'      => 'Malang',
                'nomor_kamar' => 'A02',
                'id_penyewa'  => null,
                'harga'       => 800000,
            ],
            [
                'lokasi'      => 'Surabaya',
                'nomor_kamar' => 'B02',
                'id_penyewa'  => null,
                'harga'       => 900000,
            ],
        ];

        foreach ($rooms as $data) {
            TempatKos::create($data);
        }
    }
}
