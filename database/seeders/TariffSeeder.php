<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tariff;
use App\Models\IncomeType;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $userId = \DB::table('users')->value('id');

        $rows = [
            ['income' => 'SPP Bulanan', 'class' => 'TK', 'billing' => 'monthly', 'amount' => 450000],
            ['income' => 'SPP Bulanan', 'class' => 'KB', 'billing' => 'monthly', 'amount' => 400000],
            ['income' => 'Uang Pendaftaran', 'class' => 'TK', 'billing' => 'once', 'amount' => 750000],
            ['income' => 'Biaya Seragam', 'class' => 'KB', 'billing' => 'once', 'amount' => 300000],
        ];

        foreach ($rows as $row) {
            $incomeType = IncomeType::where('name', $row['income'])->first();

            if (! $incomeType) {
                continue;
            }

            Tariff::firstOrCreate(
                [
                    'income_type_id' => $incomeType->id,
                    'class_category' => $row['class'],
                ],
                [
                    'billing_type' => $row['billing'],
                    'amount' => $row['amount'],
                    'is_active' => true,
                    'proposed_by' => $userId,
                ]
            );
        }
    }
}