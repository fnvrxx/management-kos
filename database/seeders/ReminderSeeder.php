<?php

namespace Database\Seeders;

use App\Models\Penyewa;
use App\Models\Reminder;
use Illuminate\Database\Seeder;

class ReminderSeeder extends Seeder
{
    public function run(): void
    {
        $penyewas = Penyewa::all();

        $reminders = [
            [
                'id_penyewa'       => $penyewas[0]->id,
                'end_date'         => '2026-03-01',
                'broadcast'        => false,
                'history_reminder' => null,
            ],
            [
                'id_penyewa'       => $penyewas[1]->id,
                'end_date'         => '2026-03-03',
                'broadcast'        => false,
                'history_reminder' => null,
            ],
            [
                'id_penyewa'       => $penyewas[2]->id,
                'end_date'         => '2026-04-15',
                'broadcast'        => false,
                'history_reminder' => null,
            ],
        ];

        foreach ($reminders as $data) {
            Reminder::create($data);
        }
    }
}
