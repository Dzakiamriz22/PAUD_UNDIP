<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VirtualAccount;
use App\Models\IncomeType;

class VirtualAccountSeeder extends Seeder
{
    public function run(): void
    {
        $incomeTypes = IncomeType::all();

        foreach ($incomeTypes as $type) {
            VirtualAccount::create([
                'income_type_id' => $type->id,
                'bank_name'      => 'BNI',
                'va_number'      => '98888' . rand(100000000, 999999999),
                'is_active'      => true,
            ]);
        }
    }
}