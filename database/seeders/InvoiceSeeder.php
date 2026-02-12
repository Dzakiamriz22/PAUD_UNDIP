<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Tariff;
use App\Models\User;
use App\Models\StudentClassHistory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Memulai seeder Invoice untuk Januari - 10 Februari 2026...');

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

        // Define periods: January 2026 (full month) + February 1-10 2026
        $periods = [
            ['month' => 1, 'year' => 2026, 'label' => 'Januari 2026'],
            ['month' => 2, 'year' => 2026, 'label' => 'Februari 2026 (1-10)'],
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

                // Get SPP tariff for this class category
                $tariff = Tariff::whereHas('incomeType', function($q) {
                    $q->where('name', 'SPP Siswa Lama');
                })
                ->where('class_category', $classCategory)
                ->where('billing_type', 'monthly')
                ->where('is_active', true)
                ->first();

                if (!$tariff) {
                    $this->command->warn("⚠️  Tarif SPP untuk kelas {$classCategory} tidak ditemukan, dilewati.");
                    continue;
                }

                // Create invoice
                $issuedDate = Carbon::create($period['year'], $period['month'], rand(1, 5), rand(8, 10), 0, 0);
                $dueDate = Carbon::create($period['year'], $period['month'], 10);
                
                // 70% invoices are paid, 30% unpaid
                $isPaid = rand(1, 100) <= 70;
                
                $paidAt = null;
                if ($isPaid) {
                    // Paid between issue date and end of period
                    if ($period['month'] == 2) {
                        // February 1-10
                        $paidAt = Carbon::create($period['year'], $period['month'], rand(1, 10), rand(9, 16), rand(0, 59), 0);
                    } else {
                        // January: full month
                        $paidAt = Carbon::create($period['year'], $period['month'], rand(1, 31), rand(9, 16), rand(0, 59), 0);
                    }
                }

                $invoice = Invoice::create([
                    'id' => (string) Str::uuid(),
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'issued_at' => $issuedDate,
                    'due_date' => $dueDate,
                    'paid_at' => $paidAt,
                    'sub_total' => $tariff->amount,
                    'discount_amount' => 0,
                    'total_amount' => $tariff->amount,
                    'va_number' => '8808' . str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                    'va_bank' => 'BNI',
                    'created_by' => $creator->id,
                ]);

                // Update status after creation (the model's booted method sets it to unpaid)
                if ($isPaid) {
                    $invoice->update(['status' => 'paid']);
                }

                // Create invoice item
                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'tariff_id' => $tariff->id,
                    'period_month' => $period['month'],
                    'period_year' => $period['year'],
                    'final_amount' => $tariff->amount,
                    'description' => "SPP {$period['label']} - {$classCategory}",
                ]);

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
