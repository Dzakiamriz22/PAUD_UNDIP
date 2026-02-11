<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUser(
            username: 'superadmin',
            email: 'superadmin@paud.test',
            firstname: 'Super',
            lastname: 'Admin',
            role: 'super_admin'
        );

        $this->createUser(
            username: 'admin',
            email: 'admin@paud.test',
            firstname: 'Admin',
            lastname: 'PAUD',
            role: 'admin'
        );

        $this->createUser(
            username: 'guru',
            email: 'guru@paud.test',
            firstname: 'Guru',
            lastname: 'PAUD',
            role: 'guru'
        );

        $this->createUser(
            username: 'bendahara',
            email: 'bendahara@paud.test',
            firstname: 'Bendahara',
            lastname: 'PAUD',
            role: 'bendahara'
        );

        $this->createUser(
            username: 'kepsek',
            email: 'kepsek@paud.test',
            firstname: 'Kepala',
            lastname: 'Sekolah',
            role: 'kepala_sekolah'
        );

        $this->createUser(
            username: 'auditor',
            email: 'auditor@paud.test',
            firstname: 'Auditor',
            lastname: 'PAUD',
            role: 'auditor'
        );
    }

    private function createUser(
        string $username,
        string $email,
        string $firstname,
        string $lastname,
        string $role
    ): void {
        $user = User::where('username', $username)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'username'  => $username,
                'email'     => $email,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'password'  => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // syncRoles aman untuk re-run
        $user->syncRoles([$role]);
    }
}