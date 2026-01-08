<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tariff;
use App\Models\IncomeType;
use Illuminate\Support\Str;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $spp = IncomeType::where('name', 'SPP Bulanan')->first();

        Tariff::updateOrCreate(
            ['income_type_id' => $spp->id],
            [
                'id' => Str::uuid(),
                'amount' => 150000,
                'description' => 'SPP bulanan siswa',
            ]
        );
    }
}
