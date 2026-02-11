<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\ModelHasRole;
use App\Models\RoleHasScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // Get all active academic year
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        
        if (!$activeYear) {
            $this->command->warn('No active academic year found. Skipping teacher creation.');
            return;
        }

        // Get all classes for this academic year
        $classes = SchoolClass::where('academic_year_id', $activeYear->id)->get();
        
        // Get guru role
        $guruRole = Role::where('name', 'guru')->first();
        if (!$guruRole) {
            $this->command->error('Guru role not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }

        foreach ($classes as $index => $class) {
            // Skip if teacher already assigned
            if ($class->homeroom_teacher_id) {
                $this->command->info("⊘ Class {$class->category} already has a teacher assigned");
                continue;
            }

            // Create teacher for this class
            $username = "guru_{$class->category}_" . ($index + 1);
            $email = "{$username}@paud.test";
            
            $user = User::where('username', $username)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'username'  => $username,
                    'email'     => $email,
                    'firstname' => "Guru",
                    'lastname'  => "{$class->category} " . ($index + 1),
                    'password'  => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            }

            // Assign guru role
            $user->syncRoles(['guru']);

            // Get the ModelHasRole record for this user-role assignment
            $modelHasRole = ModelHasRole::where([
                'role_id' => $guruRole->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ])->first();

            if ($modelHasRole) {
                // Assign role scope (class scope) to this role assignment
                RoleHasScope::updateOrCreate(
                    [
                        'model_role_id' => $modelHasRole->id,
                        'scope_type' => SchoolClass::class,
                        'scope_id' => $class->id,
                    ],
                    [
                        'model_role_id' => $modelHasRole->id,
                        'scope_type' => SchoolClass::class,
                        'scope_id' => $class->id,
                    ]
                );
            }

            // Assign teacher to class
            $class->update([
                'homeroom_teacher_id' => $user->id,
            ]);

            $this->command->info("✓ Created teacher: {$username} with scope to class {$class->category} ({$class->code})");
        }
    }
}
