<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\VirtualAccount;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Memulai seeder Invoice realistis (Januari - 10 Februari 2026)...');

        // Get academic year 2025/2026
        $academicYear = AcademicYear::where('year', '2025/2026')->first();
        
        if (!$academicYear) {
            $this->command->error('❌ Tahun ajaran 2025/2026 belum ada. Jalankan AcademicYearSeeder terlebih dahulu.');
            return;
        }

        // Get first user as creator
        $creator = User::first();
        if (!$creator) {
            $this->command->error('❌ User belum ada di database.');
            return;
        }

        // Get all students with their active class
        $students = Student::with(['activeClass.classRoom'])->get();
        
        if ($students->isEmpty()) {
            $this->command->warn('⚠️  Tidak ada siswa di database.');
            return;
        }

        $this->command->info("📊 Ditemukan {$students->count()} siswa");

        $va = VirtualAccount::where('is_active', true)->orderBy('id')->first();
        $vaBank = $va?->bank_name ?? 'BNI';
        $vaNumber = $va?->va_number ?? '1234567890';

        $tariffs = Tariff::with('incomeType')->where('is_active', true)->get();
        $tariffMap = [];
        foreach ($tariffs as $tariff) {
            $incomeName = $tariff->incomeType?->name;
            if (!$incomeName) {
                continue;
            }
            $tariffMap[$incomeName][$tariff->class_category][$tariff->billing_type] = $tariff;
        }

        // Define periods: January 2026 (full month) + February 1-10 2026
        $periods = [
            ['month' => 1, 'year' => 2026, 'label' => 'Januari 2026', 'days' => 31],
            ['month' => 2, 'year' => 2026, 'label' => 'Februari 2026 (1-10)', 'days' => 10],
        ];

        $totalInvoices = 0;
        $totalPaid = 0;
        $totalUnpaid = 0;

        foreach ($periods as $period) {
            $this->command->info("\n📅 Membuat invoice untuk {$period['label']}...");
            
            foreach ($students as $student) {
                // Get student's active class
                $classHistory = $student->activeClass;
                
                if (!$classHistory || !$classHistory->classRoom) {
                    $this->command->warn("⚠️  Siswa {$student->name} tidak memiliki kelas aktif, dilewati.");
                    continue;
                }

                $classCategory = $classHistory->classRoom->category;

                $items = [];

                $sppTariff = $tariffMap['SPP Siswa Lama'][$classCategory]['monthly'] ?? null;
                if ($sppTariff) {
                    $items[] = [
                        'tariff' => $sppTariff,
                        'period_month' => $period['month'],
                        'period_year' => $period['year'],
                        'period_day' => null,
                        'description' => "SPP {$period['label']} - {$classCategory}",
                    ];
                }

                $isTpa = in_array($classCategory, ['TPA_SD', 'TPA_TK', 'TPA_KB'], true);
                $dailyTariff = $tariffMap['SPP Harian'][$classCategory]['daily'] ?? null;
                if ($isTpa && $dailyTariff && rand(1, 100) <= 45) {
                    $dayCount = $period['month'] === 2 ? rand(1, 3) : rand(2, 6);
                    $pickedDays = collect(range(1, $period['days']))
                        ->shuffle()
                        ->take($dayCount)
                        ->sort()
                        ->values();

                    foreach ($pickedDays as $day) {
                        $items[] = [
                            'tariff' => $dailyTariff,
                            'period_month' => null,
                            'period_year' => null,
                            'period_day' => Carbon::create($period['year'], $period['month'], $day),
                            'description' => "SPP Harian {$day} {$period['label']} - {$classCategory}",
                        ];
                    }
                }

                if ($period['month'] === 1) {
                    if (rand(1, 100) <= 18) {
                        $registration = $tariffMap['Uang Pendaftaran'][$classCategory]['once'] ?? null;
                        if ($registration) {
                            $items[] = [
                                'tariff' => $registration,
                                'period_month' => $period['month'],
                                'period_year' => $period['year'],
                                'period_day' => null,
                                'description' => "Uang Pendaftaran - {$classCategory}",
                            ];
                        }
                    }

                    if (rand(1, 100) <= 20) {
                        $sarana = $tariffMap['Biaya Sarana & Prasarana'][$classCategory]['yearly'] ?? null;
                        if ($sarana) {
                            $items[] = [
                                'tariff' => $sarana,
                                'period_month' => $period['month'],
                                'period_year' => $period['year'],
                                'period_day' => null,
                                'description' => "Biaya Sarana & Prasarana - {$classCategory}",
                            ];
                        }
                    }

                    if (rand(1, 100) <= 17) {
                        $perlengkapan = $tariffMap['Biaya Perlengkapan & Kegiatan'][$classCategory]['yearly'] ?? null;
                        if ($perlengkapan) {
                            $items[] = [
                                'tariff' => $perlengkapan,
                                'period_month' => $period['month'],
                                'period_year' => $period['year'],
                                'period_day' => null,
                                'description' => "Biaya Perlengkapan & Kegiatan - {$classCategory}",
                            ];
                        }
                    }

                    if (rand(1, 100) <= 14) {
                        $seragam = $tariffMap['Biaya Seragam'][$classCategory]['once'] ?? null;
                        if ($seragam) {
                            $items[] = [
                                'tariff' => $seragam,
                                'period_month' => $period['month'],
                                'period_year' => $period['year'],
                                'period_day' => null,
                                'description' => "Biaya Seragam - {$classCategory}",
                            ];
                        }
                    }

                    if ($isTpa && rand(1, 100) <= 22) {
                        $daftarUlang = $tariffMap['Daftar Ulang TPA'][$classCategory]['yearly'] ?? null;
                        if ($daftarUlang) {
                            $items[] = [
                                'tariff' => $daftarUlang,
                                'period_month' => $period['month'],
                                'period_year' => $period['year'],
                                'period_day' => null,
                                'description' => "Daftar Ulang TPA - {$classCategory}",
                            ];
                        }
                    }
                }

                if (rand(1, 100) <= 12) {
                    $penalty = $tariffMap['Denda Keterlambatan'][$classCategory]['penalty'] ?? null;
                    if ($penalty) {
                        $items[] = [
                            'tariff' => $penalty,
                            'period_month' => $period['month'],
                            'period_year' => $period['year'],
                            'period_day' => null,
                            'description' => "Denda Keterlambatan - {$classCategory}",
                        ];
                    }
                }

                if (rand(1, 100) <= 12) {
                    $discount = $tariffMap['Diskon Sarana & Prasarana Anak Kembar'][$classCategory]['once'] ?? null;
                    if ($discount) {
                        $items[] = [
                            'tariff' => $discount,
                            'period_month' => $period['month'],
                            'period_year' => $period['year'],
                            'period_day' => null,
                            'description' => "Diskon Anak Kembar - {$classCategory}",
                        ];
                    }
                }

                if (in_array($classCategory, ['TK', 'TPA_TK'], true) && rand(1, 100) <= 10) {
                    $discount = $tariffMap['Diskon Alumni KB Permata Undip'][$classCategory]['once'] ?? null;
                    if ($discount) {
                        $items[] = [
                            'tariff' => $discount,
                            'period_month' => $period['month'],
                            'period_year' => $period['year'],
                            'period_day' => null,
                            'description' => "Diskon Alumni KB - {$classCategory}",
                        ];
                    }
                }

                if (empty($items)) {
                    $this->command->warn("⚠️  Tidak ada item untuk {$student->name}, dilewati.");
                    continue;
                }

                $subTotal = 0;
                $discountTotal = 0;
                foreach ($items as $item) {
                    $amount = (float) $item['tariff']->amount;
                    if ($item['tariff']->incomeType?->is_discount) {
                        $discountTotal += $amount;
                    } else {
                        $subTotal += $amount;
                    }
                }

                $totalAmount = max(0, $subTotal - $discountTotal);

                // Create invoice
                $issuedDate = Carbon::create($period['year'], $period['month'], rand(1, 5), rand(8, 10), 0, 0);
                $dueDate = Carbon::create($period['year'], $period['month'], 10);
                
                // 70% invoices are paid, 30% unpaid
                $isPaid = rand(1, 100) <= 70;
                
                $paidAt = null;
                if ($isPaid) {
                    $latePayment = rand(1, 100) <= 30;
                    $dayMax = $latePayment ? min($period['days'], 20) : min($period['days'], 10);
                    $paidDay = $latePayment ? rand(11, $dayMax) : rand(1, min(10, $period['days']));
                    $paidAt = Carbon::create($period['year'], $period['month'], $paidDay, rand(9, 16), rand(0, 59), 0);
                }

                $invoice = Invoice::create([
                    'id' => (string) Str::uuid(),
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'issued_at' => $issuedDate,
                    'due_date' => $dueDate,
                    'paid_at' => $paidAt,
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountTotal,
                    'total_amount' => $totalAmount,
                    'va_number' => $vaNumber,
                    'va_bank' => $vaBank,
                    'created_by' => $creator->id,
                ]);

                // Update status after creation (the model's booted method sets it to unpaid)
                if ($isPaid) {
                    $invoice->update(['status' => 'paid']);
                }

                foreach ($items as $item) {
                    InvoiceItem::create([
                        'id' => (string) Str::uuid(),
                        'invoice_id' => $invoice->id,
                        'tariff_id' => $item['tariff']->id,
                        'period_month' => $item['period_month'],
                        'period_year' => $item['period_year'],
                        'period_day' => $item['period_day'],
                        'final_amount' => (float) $item['tariff']->amount,
                        'description' => $item['description'],
                    ]);
                }

                $totalInvoices++;
                if ($isPaid) {
                    $totalPaid++;
                } else {
                    $totalUnpaid++;
                }
            }
        }

        $this->command->info("\n✅ Seeder Invoice selesai!");
        $this->command->info("📊 Total invoice dibuat: {$totalInvoices}");
        $this->command->info("✅ Lunas: {$totalPaid}");
        $this->command->info("⏳ Belum lunas: {$totalUnpaid}");
    }
}
