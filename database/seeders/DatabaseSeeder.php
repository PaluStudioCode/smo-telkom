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
            RoleUserSeeder::class,       // 3 default role accounts
            UserSeeder::class,           // 8 additional dummy users
            OrderStatusSeeder::class,    // ~40 order status records
            OrderEdkSeeder::class,       // ~35 order EDK records
            CompletionRecordSeeder::class, // completion records for completed orders/EDKs
            ActivityLogSeeder::class,    // activity logs across all modules
        ]);
    }
}

