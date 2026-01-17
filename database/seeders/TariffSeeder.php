<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tariff;
use App\Models\IncomeType;
use Illuminate\Support\Facades\DB;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->value('id');

        if (! $userId) {
            $this->command->error('User belum ada, TariffSeeder dibatalkan');
            return;
        }

        $tariffs = [

            // 1️⃣ UANG PENDAFTARAN (once)
            ['Uang Pendaftaran', 'TK',       'once', 300000],
            ['Uang Pendaftaran', 'KB',       'once', 300000],
            ['Uang Pendaftaran', 'TPA_PAUD', 'once', 300000],
            ['Uang Pendaftaran', 'TPA_SD',   'once', 300000],
            ['Uang Pendaftaran', 'TPA_TK',   'once', 300000],
            ['Uang Pendaftaran', 'TPA_KB',   'once', 300000],

            // 2️⃣ BIAYA SARANA & PRASARANA (yearly)
            ['Biaya Sarana & Prasarana', 'TK',       'yearly', 5500000],
            ['Biaya Sarana & Prasarana', 'KB',       'yearly', 4000000],
            ['Biaya Sarana & Prasarana', 'TPA_PAUD', 'yearly', 2500000],
            ['Biaya Sarana & Prasarana', 'TPA_SD',   'yearly', 2500000],
            ['Biaya Sarana & Prasarana', 'TPA_TK',   'yearly', 8000000],
            ['Biaya Sarana & Prasarana', 'TPA_KB',   'yearly', 6500000],

            // 3️⃣ BIAYA PERLENGKAPAN & KEGIATAN (yearly)
            ['Biaya Perlengkapan & Kegiatan', 'TK',       'yearly', 1500000],
            ['Biaya Perlengkapan & Kegiatan', 'KB',       'yearly', 1400000],
            ['Biaya Perlengkapan & Kegiatan', 'TPA_TK',   'yearly', 1500000],
            ['Biaya Perlengkapan & Kegiatan', 'TPA_KB',   'yearly', 1500000],

            // 4️⃣ BIAYA SERAGAM (once)
            ['Biaya Seragam', 'TK',       'once', 540000],
            ['Biaya Seragam', 'KB',       'once', 360000],
            ['Biaya Seragam', 'TPA_PAUD', 'once', 180000],
            ['Biaya Seragam', 'TPA_TK',   'once', 540000],
            ['Biaya Seragam', 'TPA_KB',   'once', 360000],

            // 5️⃣ SPP SISWA LAMA (monthly)
            ['SPP Siswa Lama', 'TK',       'monthly', 540000],
            ['SPP Siswa Lama', 'KB',       'monthly', 360000],
            ['SPP Siswa Lama', 'TPA_PAUD', 'monthly', 180000],
            ['SPP Siswa Lama', 'TPA_TK',   'monthly', 540000],
            ['SPP Siswa Lama', 'TPA_KB',   'monthly', 360000],

            // 6️⃣ SPP HARIAN (daily)
            ['SPP Harian', 'TK',       'daily', 450000],
            ['SPP Harian', 'KB',       'daily', 350000],
            ['SPP Harian', 'TPA_PAUD', 'daily', 900000],
            ['SPP Harian', 'TPA_SD',   'daily', 700000],
            ['SPP Harian', 'TPA_TK',   'daily', 1250000],
            ['SPP Harian', 'TPA_KB',   'daily', 1150000],

            // 7️⃣ DENDA KETERLAMBATAN (penalty)
            ['Denda Keterlambatan', 'TPA_SD', 'penalty', 20000],
            ['Denda Keterlambatan', 'TPA_TK', 'penalty', 20000],
            ['Denda Keterlambatan', 'TPA_KB', 'penalty', 20000],

            // 8️⃣ DISKON ALUMNI KB PERMATA UNDIP (once)
            ['Diskon Alumni KB Permata Undip', 'TK',     'once', 500000],
            ['Diskon Alumni KB Permata Undip', 'TPA_TK', 'once', 500000],

            // 9️⃣ DISKON SARANA & PRASARANA ANAK KEMBAR (once)
            ['Diskon Sarana & Prasarana Anak Kembar', 'TK',       'once', 250000],
            ['Diskon Sarana & Prasarana Anak Kembar', 'KB',       'once', 250000],
            ['Diskon Sarana & Prasarana Anak Kembar', 'TPA_PAUD', 'once', 250000],
            ['Diskon Sarana & Prasarana Anak Kembar', 'TPA_SD',   'once', 250000],
            ['Diskon Sarana & Prasarana Anak Kembar', 'TPA_TK',   'once', 250000],
            ['Diskon Sarana & Prasarana Anak Kembar', 'TPA_KB',   'once', 250000],

            // 🔟 DAFTAR ULANG TPA (yearly)
            ['Daftar Ulang TPA', 'TPA_PAUD', 'yearly', 800000],
            ['Daftar Ulang TPA', 'TPA_SD',   'yearly', 800000],
            ['Daftar Ulang TPA', 'TPA_TK',   'yearly', 800000],
            ['Daftar Ulang TPA', 'TPA_KB',   'yearly', 800000],
        ];

        foreach ($tariffs as [$incomeName, $class, $billing, $amount]) {

            $incomeType = IncomeType::where('name', $incomeName)->first();

            if (! $incomeType) {
                $this->command->warn("IncomeType '{$incomeName}' tidak ditemukan");
                continue;
            }

            Tariff::firstOrCreate(
                [
                    'income_type_id' => $incomeType->id,
                    'class_category' => $class,
                    'billing_type'   => $billing,
                ],
                [
                    'amount'      => $amount,
                    'is_active'   => true,
                    'proposed_by' => $userId,
                    'approved_at' => now(),
                ]
            );
        }
    }
}