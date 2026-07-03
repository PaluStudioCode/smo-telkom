<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed the three primary role accounts.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@smo.test',
                'role' => User::ROLE_SUPER_ADMIN,
                'phone' => '081100000001',
                'bio' => 'Akun utama untuk pengelolaan sistem.',
            ],
            [
                'name' => 'Admin Inputer',
                'email' => 'admin.inputer@smo.test',
                'role' => User::ROLE_ADMIN_INPUTER,
                'phone' => '081100000002',
                'bio' => 'Akun inputer untuk pengelolaan data monitoring.',
            ],
            [
                'name' => 'Account Manager',
                'email' => 'account.manager@smo.test',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081100000003',
                'bio' => 'Akun account manager untuk monitoring data terkait.',
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
