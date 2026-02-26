<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,          // 0. Admin user
            PenyewaSeeder::class,       // 1. Tenants (no dependencies)
            TempatKosSeeder::class,     // 2. Rooms (depends on penyewa)
            TransaksiKosSeeder::class,  // 3. Transactions (depends on penyewa + tempat_kos)
            ReminderSeeder::class,      // 4. Reminders (depends on penyewa)
            PengeluaranSeeder::class,   // 5. Expenses (no dependencies)
        ]);
    }
}
