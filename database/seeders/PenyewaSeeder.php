<?php

namespace Database\Seeders;

use App\Models\Penyewa;
use Illuminate\Database\Seeder;

class PenyewaSeeder extends Seeder
{
    public function run(): void
    {
        $penyewas = [
            [
                'nama_lengkap'      => 'Budi Santoso',
                'no_wa'             => '081234567890',
                'start_date'        => '2025-01-01',
                'rencana_lama_kos'  => '2025-12-31',
                'end_date'          => null,
            ],
            [
                'nama_lengkap'      => 'Siti Rahayu',
                'no_wa'             => '082345678901',
                'start_date'        => '2025-03-01',
                'rencana_lama_kos'  => '2026-03-01',
                'end_date'          => null,
            ],
            [
                'nama_lengkap'      => 'Ahmad Fauzi',
                'no_wa'             => '083456789012',
                'start_date'        => '2025-06-15',
                'rencana_lama_kos'  => '2026-06-15',
                'end_date'          => null,
            ],
        ];

        foreach ($penyewas as $data) {
            Penyewa::create($data);
        }
    }
}
