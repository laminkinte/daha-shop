<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Default super admin for every environment, including production.
     * Idempotent (matched on email) so it's safe to re-run. Change the
     * password after first login.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'shopdaha0@gmail.com'],
            [
                'name' => 'Daha Shop Super Admin',
                'phone' => '+2348099999999',
                'role' => UserRole::Admin,
                'is_super_admin' => true,
                'password' => 'password',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
    }
}
