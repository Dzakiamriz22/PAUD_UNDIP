<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VirtualAccount;
use App\Models\IncomeType;

class VirtualAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Create a single shared virtual account used for all payment types.
        $incomeTypes = IncomeType::all();

        if (VirtualAccount::count() === 0) {
            $va = VirtualAccount::create([
                'bank_name' => 'BNI',
                'va_number' => '98888' . rand(100000000, 999999999),
                'is_active' => true,
            ]);

            if ($incomeTypes->isNotEmpty()) {
                $va->incomeTypes()->sync($incomeTypes->pluck('id')->toArray());
            }
        }
    }
}