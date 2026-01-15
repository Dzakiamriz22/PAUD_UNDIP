<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\IncomeType;
use App\Models\Tariff;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\VirtualAccount;

use Illuminate\Support\Facades\DB;

class GenerateInvoices extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationLabel = 'Generate Invoice';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static string $view = 'filament.pages.generate-invoices';

    public array $data = [];

    /* ================= FORM ================= */

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([

                /* ===== TAHUN AJARAN ===== */

                Forms\Components\Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->required()
                    ->options(
                        AcademicYear::query()
                            ->get()
                            ->mapWithKeys(fn ($ay) => [
                                $ay->id => "{$ay->year} — " . ucfirst($ay->semester),
                            ])
                    ),

                /* ===== KELAS ===== */

                Forms\Components\Select::make('class_id')
                    ->label('Kelas')
                    ->required()
                    ->reactive()
                    ->options(SchoolClass::pluck('code', 'id'))
                    ->afterStateUpdated(function (callable $set, $state) {

                        // ⬇️ Default: pilih SEMUA siswa di kelas
                        $studentIds = Student::whereHas('activeClass', fn ($q) =>
                            $q->where('class_id', $state)
                        )->pluck('id')->toArray();

                        $set('student_ids', $studentIds);
                    }),

                /* ===== SISWA (DEFAULT SEMUA TERPILIH) ===== */

                Forms\Components\CheckboxList::make('student_ids')
                    ->label('Siswa')
                    ->columns(2)
                    ->required()
                    ->helperText('Hilangkan centang untuk siswa yang tidak ingin dibuatkan invoice')
                    ->options(function (callable $get) {

                        if (! $get('class_id')) {
                            return [];
                        }

                        return Student::whereHas('activeClass', fn ($q) =>
                            $q->where('class_id', $get('class_id'))
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id');
                    }),

                /* ===== JENIS PEMBAYARAN ===== */

                Forms\Components\Select::make('income_type_id')
                    ->label('Jenis Pembayaran')
                    ->required()
                    ->reactive()
                    ->options(function (callable $get) {

                        if (! $get('class_id')) {
                            return [];
                        }

                        $class = SchoolClass::find($get('class_id'));

                        return IncomeType::whereHas('tariffs', fn ($q) =>
                            $q->where('class_category', $class->category)
                              ->where('is_active', true)
                        )->pluck('name', 'id');
                    }),

                /* ===== TARIF ===== */

                Forms\Components\Select::make('tariff_id')
                    ->label('Tarif')
                    ->required()
                    ->options(function (callable $get) {

                        if (! $get('class_id') || ! $get('income_type_id')) {
                            return [];
                        }

                        $class = SchoolClass::find($get('class_id'));

                        return Tariff::where('income_type_id', $get('income_type_id'))
                            ->where('class_category', $class->category)
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($tariff) => [
                                $tariff->id =>
                                    "{$tariff->incomeType->name} ({$tariff->billing_type_label}) — Rp "
                                    . number_format($tariff->amount, 0, ',', '.'),
                            ]);
                    }),

                /* ===== VIRTUAL ACCOUNT ===== */

                Forms\Components\Select::make('virtual_account_id')
                    ->label('Virtual Account')
                    ->required()
                    ->options(fn (callable $get) =>
                        VirtualAccount::where('income_type_id', $get('income_type_id'))
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($va) => [
                                $va->id => "{$va->bank_name} — {$va->va_number}",
                            ])
                    ),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required(),
            ]);
    }

    /* ================= ACTION ================= */

    public function generate(): void
    {
        $data = $this->data;

        DB::transaction(function () use ($data) {

            $class    = SchoolClass::findOrFail($data['class_id']);
            $students = Student::whereIn('id', $data['student_ids'])->get();

            abort_if($students->isEmpty(), 400, 'Minimal pilih satu siswa');

            $tariff = Tariff::findOrFail($data['tariff_id']);
            $va     = VirtualAccount::findOrFail($data['virtual_account_id']);

            foreach ($students as $student) {

                $invoice = Invoice::create([
                    'student_id'       => $student->id,
                    'class_id'         => $class->id,
                    'academic_year_id' => $data['academic_year_id'],
                    'income_type_id'   => $data['income_type_id'],
                    'va_number'        => $va->va_number,
                    'va_bank'          => $va->bank_name,
                    'due_date'         => $data['due_date'],
                ]);

                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'tariff_id'       => $tariff->id,
                    'original_amount' => $tariff->amount,
                    'discount_amount' => 0,
                    'final_amount'    => $tariff->amount,
                    'description'     => $tariff->incomeType->name,
                ]);

                $invoice->recalculateTotal();
            }
        });

        Notification::make()
            ->title('Invoice berhasil digenerate')
            ->success()
            ->send();

        $this->form->fill([]);
    }
}