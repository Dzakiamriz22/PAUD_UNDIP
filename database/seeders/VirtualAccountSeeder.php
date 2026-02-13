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
        $defaultIncomeTypeId = $incomeTypes->first()?->id;

        if (VirtualAccount::count() === 0) {
            $va = VirtualAccount::create([
                'income_type_id' => $defaultIncomeTypeId,
                'bank_name' => 'BNI',
                'va_number' => '1234567890',
                'is_active' => true,
            ]);

            if ($incomeTypes->isNotEmpty()) {
                $va->incomeTypes()->sync($incomeTypes->pluck('id')->toArray());
            }
        }
    }
}