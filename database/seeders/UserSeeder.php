<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed additional dummy users for development/testing.
     */
    public function run(): void
    {
        $users = [
            // Admin Inputers
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@smo.test',
                'role' => User::ROLE_ADMIN_INPUTER,
                'phone' => '081234567001',
                'bio' => 'Admin Inputer - Tim Provisioning Palu.',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@smo.test',
                'role' => User::ROLE_ADMIN_INPUTER,
                'phone' => '081234567002',
                'bio' => 'Admin Inputer - Tim Provisioning Manado.',
            ],
            [
                'name' => 'Dewi Kartika',
                'email' => 'dewi.kartika@smo.test',
                'role' => User::ROLE_ADMIN_INPUTER,
                'phone' => '081234567003',
                'bio' => 'Admin Inputer - Tim Billing & Administrasi.',
            ],

            // Account Managers
            [
                'name' => 'Ahmad Fauzan',
                'email' => 'ahmad.fauzan@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567004',
                'bio' => 'Account Manager - Segmen Pemerintah Kota Palu.',
            ],
            [
                'name' => 'Rina Wulandari',
                'email' => 'rina.wulandari@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567005',
                'bio' => 'Account Manager - Segmen Pemerintah Provinsi Sulteng.',
            ],
            [
                'name' => 'Hendra Pratama',
                'email' => 'hendra.pratama@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567006',
                'bio' => 'Account Manager - Segmen BUMN & Korporasi.',
            ],
            [
                'name' => 'Putri Rahayu',
                'email' => 'putri.rahayu@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567007',
                'bio' => 'Account Manager - Segmen Pemerintah Kabupaten.',
            ],
            [
                'name' => 'Wahyu Setiawan',
                'email' => 'wahyu.setiawan@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567008',
                'bio' => 'Account Manager - Segmen Pendidikan & Kesehatan.',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    ...$user,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }
    }
}
