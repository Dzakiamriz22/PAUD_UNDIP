<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class VerifyAllUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Update all users to set email_verified_at to current timestamp
        User::whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->command->info('✓ All users have been verified');
    }
}
