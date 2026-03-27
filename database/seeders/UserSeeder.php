<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // $this->createUser(
        //     username: 'superadmin',
        //     email: 'superadmin@paud.test',
        //     firstname: 'Super',
        //     lastname: 'Admin',
        //     role: 'super_admin'
        // );

        $this->createUser(
            username: 'admin',
            email: 'admin@paud.test',
            firstname: 'Admin',
            lastname: 'PAUD',
            role: 'operator'
        );

        $this->createUser(
            username: 'guru',
            email: 'guru@paud.test',
            firstname: 'Guru',
            lastname: 'PAUD',
            role: 'guru'
        );

        // $this->createUser(
        //     username: 'bendahara',
        //     email: 'bendahara@paud.test',
        //     firstname: 'Bendahara',
        //     lastname: 'PAUD',
        //     role: 'bendahara'
        // );

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
        // Cari user berdasarkan email ATAU username
        $user = User::where('email', $email)
            ->orWhere('username', $username)
            ->first();

        if (! $user) {
            // Kalau belum ada → buat baru
            $user = new User();
            $user->password = Hash::make('password'); // hanya saat create
        }

        // Update / isi data
        $user->username  = $username;
        $user->email     = $email;
        $user->firstname = $firstname;
        $user->lastname  = $lastname;
        $user->email_verified_at = now();

        $user->save();

        // Assign role (aman untuk re-run)
        $user->syncRoles([$role]);
    }
}