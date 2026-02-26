<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // NOTE: WithoutModelEvents removed so TransaksiKos boot() hook
    // auto-computes tgl_jatuh_tempo on rooms after seeding transactions.

    public function run(): void
    {
        $this->call([
            UserSeeder::class,              // 0. Admin user
            PenyewaSeeder::class,           // 1. Tenants (no dependencies)
            TempatKosSeeder::class,         // 2. Rooms (depends on penyewa)
            TransaksiKosSeeder::class,      // 3. Transactions (depends on penyewa + tempat_kos)
            ReminderSeeder::class,          // 4. Reminders (depends on penyewa)
            PengeluaranSeeder::class,       // 5. Expenses (no dependencies)
            TemplateMessageSeeder::class,   // 6. WhatsApp message templates
        ]);
    }
}
