<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

use App\Models\SchoolClass;
use App\Models\Tariff;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\VirtualAccount;
use App\Models\AcademicYear;

use Illuminate\Support\Facades\DB;
use App\Services\BatchInvoiceService;
use ZipArchive;
use Illuminate\Http\Response;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function create(bool $another = false): void
    {
        $data = $this->form->getState();

        $createdInvoiceIds = [];

        DB::transaction(function () use ($data, &$createdInvoiceIds) {

            $class    = SchoolClass::findOrFail($data['class_id']);
            $students = Student::whereIn('id', $data['student_ids'])->get();

            abort_if($students->isEmpty(), 400, 'Minimal pilih satu siswa');

            $tariffItems = $data['tariff_items'] ?? [];
            abort_if(empty($tariffItems), 400, 'Minimal pilih satu jenis pembayaran');

            $va = VirtualAccount::find($data['virtual_account_id'] ?? null)
                ?? VirtualAccount::where('is_active', true)->orderBy('id')->firstOrFail();
            $academicYear = AcademicYear::findOrFail($data['academic_year_id']);
            
            // Parse tahun ajaran (format: "2024/2025")
            $yearParts = explode('/', $academicYear->year);
            $startYear = (int) $yearParts[0]; // 2024
            $endYear = (int) ($yearParts[1] ?? $yearParts[0]); // 2025

            // Helper function untuk mendapatkan nama bulan
            $getMonthName = function ($month) {
                $monthNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                return $monthNames[$month] ?? $month;
            };
            
            // Helper function untuk menentukan tahun kalender berdasarkan bulan
            $getCalendarYear = function ($month, $startYear, $endYear) {
                // Bulan <= 6 (Jan-Jun) = tahun akhir (2025)
                // Bulan >= 7 (Jul-Des) = tahun awal (2024)
                return ($month <= 6) ? $endYear : $startYear;
            };

            foreach ($students as $student) {

                $invoice = Invoice::create([
                    'student_id'       => $student->id,
                    'academic_year_id' => $data['academic_year_id'],
                    'va_number'        => $va->va_number,
                    'va_bank'          => $va->bank_name,
                    'due_date'         => $data['due_date'],
                ]);

                // Buat invoice items untuk semua tarif yang dipilih
                foreach ($tariffItems as $item) {
                    if (!isset($item['tariff_id'])) {
                        continue;
                    }

                    $tariff = Tariff::with('incomeType')->findOrFail($item['tariff_id']);
                    $incomeTypeName = $tariff->incomeType->name ?? '';
                    
                    switch ($tariff->billing_type) {
                        case 'monthly':
                            // Untuk monthly: pecah range bulan menjadi multiple items
                            $startMonth = (int) ($item['start_month'] ?? now()->month);
                            $endMonth = (int) ($item['end_month'] ?? $startMonth);
                            
                            // Urutan bulan dalam tahun ajaran: Juli(7) sampai Juni(6) tahun berikutnya
                            $academicYearMonths = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];
                            
                            // Cari index start dan end dalam urutan tahun ajaran
                            $startIndex = array_search($startMonth, $academicYearMonths);
                            $endIndex = array_search($endMonth, $academicYearMonths);
                            
                            // Validasi: pastikan start dan end valid
                            if ($startIndex !== false && $endIndex !== false && $endIndex >= $startIndex) {
                                // Loop dari start sampai end (termasuk end)
                                // Setiap bulan akan dibuat sebagai invoice item terpisah
                                for ($i = $startIndex; $i <= $endIndex; $i++) {
                                    $currentMonth = $academicYearMonths[$i];
                                    
                                    // Tentukan tahun kalender berdasarkan bulan
                                    // Bulan <= 6 (Jan-Jun) = tahun akhir (2026)
                                    // Bulan >= 7 (Jul-Des) = tahun awal (2025)
                                    $calendarYear = $getCalendarYear($currentMonth, $startYear, $endYear);
                                    
                                    // Buat invoice item untuk setiap bulan
                                    InvoiceItem::create([
                                        'invoice_id'      => $invoice->id,
                                        'tariff_id'       => $tariff->id,
                                        'period_month'    => $currentMonth,
                                        'period_year'     => $calendarYear,
                                        'final_amount'    => $tariff->amount,
                                        'description'     => $incomeTypeName . ' - ' . $getMonthName($currentMonth),
                                    ]);
                                }
                            } else {
                                // Fallback: jika validasi gagal, buat item untuk start_month saja
                                $calendarYear = $getCalendarYear($startMonth, $startYear, $endYear);
                                InvoiceItem::create([
                                    'invoice_id'      => $invoice->id,
                                    'tariff_id'       => $tariff->id,
                                    'period_month'    => $startMonth,
                                    'period_year'     => $calendarYear,
                                    'final_amount'    => $tariff->amount,
                                    'description'     => $incomeTypeName . ' - ' . $getMonthName($startMonth),
                                ]);
                            }
                            break;
                            
                        case 'once':
                        case 'yearly':
                            // Untuk once dan yearly: simpan period_year (default ke tahun akhir dari academic year)
                            $periodYear = (int) ($item['period_year'] ?? $endYear);
                            
                            InvoiceItem::create([
                                'invoice_id'      => $invoice->id,
                                'tariff_id'       => $tariff->id,
                                'period_month'    => null,
                                'period_year'     => $periodYear,
                                'final_amount'    => $tariff->amount,
                                'description'     => $incomeTypeName,
                            ]);
                            break;
                            
                        case 'daily':
                            // Untuk daily: simpan tanggal (single atau range) dan pecah menjadi item per hari
                            $startDate = $item['start_date'] ?? now();
                            $useRangeDate = $item['use_range_date'] ?? false;
                            
                            // Convert ke Carbon jika belum
                            if (!$startDate instanceof \Carbon\Carbon) {
                                $startDate = \Carbon\Carbon::parse($startDate);
                            }
                            
                            // Jika checkbox range dicentang, gunakan end_date, jika tidak gunakan start_date saja
                            if ($useRangeDate && isset($item['end_date'])) {
                                $endDate = $item['end_date'];
                                if (!$endDate instanceof \Carbon\Carbon) {
                                    $endDate = \Carbon\Carbon::parse($endDate);
                                }
                                
                                // Pastikan end_date >= start_date
                                if ($endDate->lt($startDate)) {
                                    $endDate = $startDate->copy();
                                }
                            } else {
                                // Single date: end_date sama dengan start_date
                                $endDate = $startDate->copy();
                            }
                            
                            // Loop melalui setiap hari dalam range
                            $currentDate = $startDate->copy();
                            while ($currentDate->lte($endDate)) {
                                InvoiceItem::create([
                                    'invoice_id'      => $invoice->id,
                                    'tariff_id'       => $tariff->id,
                                    'period_day'      => $currentDate->format('Y-m-d'),
                                    'period_month'    => $currentDate->month,
                                    'period_year'     => $currentDate->year,
                                    'final_amount'    => $tariff->amount,
                                    'description'     => $incomeTypeName . ' - ' . $currentDate->format('d/m/Y'),
                                ]);
                                
                                // Tambah 1 hari
                                $currentDate->addDay();
                            }
                            break;
                            
                        default:
                            // Fallback untuk billing_type lain
                            $periodMonth = (int) ($item['period_month'] ?? now()->month);
                            $periodYear = (int) ($item['period_year'] ?? $endYear);
                            
                            InvoiceItem::create([
                                'invoice_id'      => $invoice->id,
                                'tariff_id'       => $tariff->id,
                                'period_month'    => $periodMonth,
                                'period_year'     => $periodYear,
                                'final_amount'    => $tariff->amount,
                                'description'     => $incomeTypeName,
                            ]);
                            break;
                    }
                }

                $invoice->recalculateTotal();
                $createdInvoiceIds[] = $invoice->id;
            }
        });

        // Jika tidak ada invoice dibuat, beri notifikasi dan redirect
        if (empty($createdInvoiceIds)) {
            Notification::make()
                ->title('Tidak ada invoice dibuat')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }

        // Ambil invoices yang baru dibuat
        $invoices = Invoice::whereIn('id', $createdInvoiceIds)->get();

        // Jika hanya 1 invoice dibuat, langsung unduh PDF tunggal
        $batchService = new BatchInvoiceService();

        $tmpDir = storage_path('app/invoice_tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

            if ($invoices->count() === 1) {
            $inv = $invoices->first();
            $pdf = $batchService->generateSinglePdf($inv);
            $safeInvoiceNumber = str_replace('/', '-', $inv->invoice_number);
            $studentName = preg_replace('/[^A-Za-z0-9\-_. ]/', '', $inv->student->name ?? 'student');
            $fileName = 'invoice-' . $safeInvoiceNumber . '-' . $studentName . '.pdf';
            $filePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($filePath, $pdf->output());
                // Tampilkan notifikasi sukses lalu arahkan ke route download sementara
                Notification::make()
                    ->title('Invoice berhasil dibuat dan PDF disimpan')
                    ->success()
                    ->send();

                $this->redirect(route('invoices.download_temp', ['filename' => $fileName]));
                return;
        }

        // Lebih dari satu: buat SATU PDF gabungan (batch)
        $pdf = $batchService->generateBatchPdf($invoices);

        $downloadName = 'invoices-' . now()->format('Ymd-His') . '.pdf';
        $filePath = $tmpDir . DIRECTORY_SEPARATOR . $downloadName;

            try {
                file_put_contents($filePath, $pdf->output());
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Gagal membuat file PDF gabungan')
                    ->danger()
                    ->send();

                $this->redirect($this->getResource()::getUrl('index'));
                return;
            }

            // Tampilkan notifikasi sukses lalu arahkan ke route download sementara untuk file gabungan
            Notification::make()
                ->title('Invoice berhasil dibuat dan PDF gabungan disimpan')
                ->success()
                ->send();

            $this->redirect(route('invoices.download_temp', ['filename' => basename($filePath)]));
            return;
    }
}
