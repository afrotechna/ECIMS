<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates one demo administrator so a fresh deployment (no terminal/tinker
 * access to create the first user by hand) has a working login out of the box.
 * Idempotent — safe to run on every deploy.
 */
class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'demo.admin@test.tahisa.or.tz'],
            [
                'name' => 'Demo Administrator',
                'surname' => 'Admin',
                'password' => 'Demo@2026',
                'role' => 'administrator',
                'must_change_password' => false,
                'profile_completed_at' => now(),
            ]
        );
    }
}
