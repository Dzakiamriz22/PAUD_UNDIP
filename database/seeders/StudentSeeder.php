<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str; 
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        for ($i = 1; $i <= 20; $i++) {
            Student::firstOrCreate(
                ['nis' => 'PAUD' . str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $faker->name(),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'birth_date' => $faker->dateTimeBetween('-6 years', '-4 years')->format('Y-m-d'),
                    'parent_name' => $faker->name(),
                    'status' => 'active',
                ]
            );
        }
    }
}
