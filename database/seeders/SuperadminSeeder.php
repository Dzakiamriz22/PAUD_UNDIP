<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;

class SuperadminSeeder extends Seeder
{
    public function run()
    {
        // Superadmin user
        $sid = Str::uuid();
        $user = User::where('email', 'superadmin@local.com')->first();

        if (!$user) {
            User::create([
                'id' => $sid,
                'username' => 'superadmin',
                'firstname' => 'Super',
                'lastname' => 'Admin',
                'email' => 'superadmin@local.com',
                'email_verified_at' => now(),
                'password' => Hash::make(config('app.default_user_password'))
            ]);
        }

        $id = User::where('email', 'superadmin@local.com')->first()->id;

        // Bind superadmin user to FilamentShield
        Artisan::call('shield:super-admin', ['--user' => $id, '--panel' => 'admin']);
    }
}
