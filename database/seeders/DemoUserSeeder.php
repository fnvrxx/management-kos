<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'demo'],
            [
                'name' => 'Demo',
                'email' => 'demo@management-kos.test',
                'password' => 'demo12345',
            ],
        );
    }
}
