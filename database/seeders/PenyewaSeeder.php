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
                'start_date'        => '2025-12-06', // Check-in 6 Des 2025
                'rencana_lama_kos'  => '2026-12-06',
                'end_date'          => null,
            ],
            [
                'nama_lengkap'      => 'Siti Rahayu',
                'no_wa'             => '082345678901',
                'start_date'        => '2026-01-10', // Check-in 10 Jan 2026
                'rencana_lama_kos'  => '2027-01-10',
                'end_date'          => null,
            ],
            [
                'nama_lengkap'      => 'Ahmad Fauzi',
                'no_wa'             => '083456789012',
                'start_date'        => '2026-01-15', // Check-in 15 Jan 2026
                'rencana_lama_kos'  => '2027-01-15',
                'end_date'          => null,
            ],
        ];

        foreach ($penyewas as $data) {
            Penyewa::create($data);
        }
    }
}
