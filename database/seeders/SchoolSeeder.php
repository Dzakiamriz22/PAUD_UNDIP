<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::updateOrCreate(
            ['name' => 'PAUD Permata UNDIP'],
            [
                'id' => Str::uuid(),
                'address' => 'Semarang',
                'contact' => 'Telp: 024-123456 | Email: paud@undip.ac.id',
                'logo_url' => null,
            ]
        );
    }
}