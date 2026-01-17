<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\IncomeType;
use App\Models\Tariff;
use App\Models\Student;
use App\Models\VirtualAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Grid;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Invoice';
    protected static ?int $navigationSort = 1;

    /* ===============================
     | FORM
     =============================== */
    public static function form(Form $form): Form
    {
        // Jika sedang melihat data yang sudah ada (View Mode)
        if ($form->getRecord()) {
            return self::viewForm($form);
        }

        // Alur Wizard untuk Create Mode
        return $form->schema([
            Wizard::make([
                /* STEP 1: TARGET SISWA */
                Wizard\Step::make('Target Siswa')
                    ->description('Pilih kelas dan daftar siswa')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('academic_year_id')
                                ->label('Tahun Ajaran')
                                ->options(function () {
                                    // Hanya mengambil tahun ajaran dengan status is_active = true
                                    return \App\Models\AcademicYear::where('is_active', true)
                                        ->pluck('year', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->default(function () {
                                    // Opsional: Otomatis memilih tahun ajaran aktif yang pertama ditemukan
                                    return \App\Models\AcademicYear::where('is_active', true)
                                        ->first()?->id;
                                }),

                            Forms\Components\Select::make('class_id')
                                ->label('Pilih Kelas')
                                ->options(SchoolClass::pluck('code', 'id'))
                                ->required()
                                ->reactive()
                                ->live()
                                ->afterStateUpdated(fn($set) => $set('student_ids', [])),
                        ]),

                        Forms\Components\CheckboxList::make('student_ids')
                            ->label('Daftar Siswa')
                            ->options(function (callable $get) {
                                if (!$get('class_id'))
                                    return [];
                                return Student::whereHas('activeClass', fn($q) => $q->where('class_id', $get('class_id')))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->descriptions(fn() => ['Pilih semua siswa atau pilih secara manual'])
                            ->columns(3) // Lebih rapi dengan 3 kolom
                            ->bulkToggleable() // UX: Tambahkan tombol "Pilih Semua"
                            ->searchable() // UX: Tambahkan fitur cari nama siswa
                            ->required()
                            ->extraAttributes(['class' => 'max-h-60 overflow-y-auto']), // Batasi tinggi agar tidak terlalu panjang
                    ]),

                /* STEP 2: RINCIAN BIAYA */
                Wizard\Step::make('Rincian Biaya')
                    ->schema([
                        Forms\Components\Repeater::make('tariff_items')
                            ->label('Item Tagihan')
                            ->live() // Memastikan seluruh repeater re-render saat ada perubahan di baris manapun
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    // 1. SELECT KATEGORI BIAYA (Dengan Logika Anti-Duplikasi)
                                    Forms\Components\Select::make('income_type_id')
                                        ->label('Kategori Biaya')
                                        ->required()
                                        ->live()
                                        ->options(function (callable $get) {
                                            // Ambil class_id dari Step 1
                                            $classId = $get('../../class_id');
                                            if (!$classId)
                                                return [];

                                            $class = \App\Models\SchoolClass::find($classId);
                                            if (!$class)
                                                return [];

                                            // Langkah A: Ambil semua Kategori yang punya tarif untuk kelas ini
                                            $availableQuery = \App\Models\IncomeType::whereHas('tariffs', function ($q) use ($class) {
                                                $q->where('class_category', $class->category)->where('is_active', true);
                                            });

                                            // Langkah B: Identifikasi apa saja yang sudah dipilih di baris LAIN
                                            $allRepeaterItems = collect($get('../../tariff_items') ?? []);
                                            $currentValue = $get('income_type_id');

                                            // Ambil ID yang sudah dipilih, tapi jangan masukkan ID baris ini (agar label tidak hilang)
                                            $otherSelectedIds = $allRepeaterItems
                                                ->pluck('income_type_id')
                                                ->filter(fn($id) => $id && $id !== $currentValue)
                                                ->toArray();

                                            return $availableQuery
                                                ->whereNotIn('id', $otherSelectedIds)
                                                ->pluck('name', 'id');
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Reset dan Auto-fill tariff_id
                                            $set('tariff_id', null);

                                            $classId = $get('../../class_id');
                                            if ($state && $classId) {
                                                $class = \App\Models\SchoolClass::find($classId);
                                                if ($class) {
                                                    $tariff = \App\Models\Tariff::where('income_type_id', $state)
                                                        ->where('class_category', $class->category)
                                                        ->where('is_active', true)
                                                        ->first();

                                                    $set('tariff_id', $tariff?->id);
                                                }
                                            }
                                        }),

                                    // 2. SELECT TARIF (Otomatis & Terkunci)
                                    Forms\Components\Select::make('tariff_id')
                                        ->label('Tarif Terpilih')
                                        ->options(\App\Models\Tariff::all()->mapWithKeys(function ($t) {
                                            // Menggunakan accessor 'billing_type_label' yang sudah Anda buat di model
                                            return [
                                                $t->id => "{$t->billing_type_label} (Rp " . number_format((float) ($t->amount ?? 0), 0, ',', '.') . ")"
                                            ];
                                        }))
                                        ->required()
                                        ->live() // Agar reactive terhadap perubahan untuk update field periode
                                        ->disabled() // Tetap terkunci agar pengisian otomatis tidak diubah manual
                                        ->dehydrated(true) // Memastikan ID tarif tetap dikirim ke server saat simpan
                                        ->placeholder('Otomatis terisi...'),
                                ]),

                                // 3. PERIODE (Conditional berdasarkan billing_type)
                                Forms\Components\Group::make([
                                    // Untuk MONTHLY: Tampilkan start_month dan end_month
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('start_month')
                                            ->label('Dari Bulan')
                                            ->options([
                                                7 => 'Juli',
                                                8 => 'Agustus',
                                                9 => 'September',
                                                10 => 'Oktober',
                                                11 => 'November',
                                                12 => 'Desember',
                                                1 => 'Januari',
                                                2 => 'Februari',
                                                3 => 'Maret',
                                                4 => 'April',
                                                5 => 'Mei',
                                                6 => 'Juni'
                                            ])
                                            ->required(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'monthly';
                                            })
                                            ->live()
                                            ->visible(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'monthly';
                                            }),

                                        Forms\Components\Select::make('end_month')
                                            ->label('Sampai Bulan')
                                            ->options(function (callable $get) {
                                                $startMonth = $get('start_month');
                                                $academicYearMonths = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];

                                                $allMonths = [
                                                    7 => 'Juli',
                                                    8 => 'Agustus',
                                                    9 => 'September',
                                                    10 => 'Oktober',
                                                    11 => 'November',
                                                    12 => 'Desember',
                                                    1 => 'Januari',
                                                    2 => 'Februari',
                                                    3 => 'Maret',
                                                    4 => 'April',
                                                    5 => 'Mei',
                                                    6 => 'Juni'
                                                ];

                                                // Jika start_month sudah dipilih, filter options agar end_month >= start_month dalam urutan tahun ajaran
                                                if ($startMonth) {
                                                    $startIndex = array_search((int) $startMonth, $academicYearMonths);
                                                    if ($startIndex !== false) {
                                                        // Hanya tampilkan bulan dari start_month sampai akhir tahun ajaran
                                                        $filteredMonths = [];
                                                        for ($i = $startIndex; $i < count($academicYearMonths); $i++) {
                                                            $month = $academicYearMonths[$i];
                                                            $filteredMonths[$month] = $allMonths[$month];
                                                        }
                                                        return $filteredMonths;
                                                    }
                                                }

                                                return $allMonths;
                                            })
                                            ->required(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'monthly';
                                            })
                                            ->live()
                                            ->reactive()
                                            ->visible(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'monthly';
                                            })
                                            ->helperText('Setiap bulan dalam range akan dibuat sebagai item terpisah'),
                                    ]),

                                    // Untuk ONCE dan YEARLY: Tampilkan period_year
                                    Forms\Components\Select::make('period_year')
                                        ->label('Tahun')
                                        ->options(function (callable $get) {
                                            // Ambil tahun dari academic_year yang dipilih
                                            $academicYearId = $get('../../../academic_year_id');
                                            if ($academicYearId) {
                                                $academicYear = \App\Models\AcademicYear::find($academicYearId);
                                                if ($academicYear) {
                                                    $yearParts = explode('/', $academicYear->year);
                                                    $startYear = (int) $yearParts[0];
                                                    $endYear = (int) ($yearParts[1] ?? $yearParts[0]);

                                                    // Tampilkan range tahun dari tahun ajaran
                                                    return array_combine(
                                                        range($startYear, $endYear),
                                                        range($startYear, $endYear)
                                                    );
                                                }
                                            }

                                            // Fallback: tahun saat ini
                                            return array_combine(
                                                range(now()->year - 1, now()->year + 1),
                                                range(now()->year - 1, now()->year + 1)
                                            );
                                        })
                                        ->default(function (callable $get) {
                                            // Default ke tahun akhir dari academic year
                                            $academicYearId = $get('../../../academic_year_id');
                                            if ($academicYearId) {
                                                $academicYear = \App\Models\AcademicYear::find($academicYearId);
                                                if ($academicYear) {
                                                    $yearParts = explode('/', $academicYear->year);
                                                    return (int) ($yearParts[1] ?? $yearParts[0]);
                                                }
                                            }
                                            return now()->year;
                                        })
                                        ->required(function (callable $get) {
                                            $tariffId = $get('tariff_id');
                                            if (!$tariffId)
                                                return false;
                                            $tariff = \App\Models\Tariff::find($tariffId);
                                            return $tariff && in_array($tariff->billing_type, ['once', 'yearly']);
                                        })
                                        ->live()
                                        ->visible(function (callable $get) {
                                            $tariffId = $get('tariff_id');
                                            if (!$tariffId)
                                                return false;
                                            $tariff = \App\Models\Tariff::find($tariffId);
                                            return $tariff && in_array($tariff->billing_type, ['once', 'yearly']);
                                        }),

                                    // Untuk DAILY: Tampilkan range tanggal (start_date dan end_date)
                                    Forms\Components\Group::make([
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Tanggal')
                                            ->required(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'daily';
                                            })
                                            ->default(now())
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->live()
                                            ->reactive()
                                            ->visible(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'daily';
                                            }),

                                        Forms\Components\Checkbox::make('use_range_date')
                                            ->label('Gunakan Range Tanggal')
                                            ->default(false)
                                            ->live()
                                            ->reactive()
                                            ->visible(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                return $tariff && $tariff->billing_type === 'daily';
                                            })
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                // Reset end_date jika checkbox tidak dicentang
                                                if (!$state) {
                                                    $set('end_date', null);
                                                } else {
                                                    // Set end_date sama dengan start_date jika checkbox dicentang
                                                    $set('end_date', $get('start_date') ?? now());
                                                }
                                            }),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('Sampai Tanggal')
                                            ->required(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                if (!$tariff || $tariff->billing_type !== 'daily') {
                                                    return false;
                                                }
                                                // Required hanya jika checkbox range dicentang
                                                return $get('use_range_date') === true;
                                            })
                                            ->default(function (callable $get) {
                                                // Default ke start_date jika checkbox dicentang
                                                return $get('start_date') ?? now();
                                            })
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->minDate(function (callable $get) {
                                                $startDate = $get('start_date');
                                                if ($startDate) {
                                                    return $startDate instanceof \Carbon\Carbon 
                                                        ? $startDate 
                                                        : \Carbon\Carbon::parse($startDate);
                                                }
                                                return null;
                                            })
                                            ->visible(function (callable $get) {
                                                $tariffId = $get('tariff_id');
                                                if (!$tariffId)
                                                    return false;
                                                $tariff = \App\Models\Tariff::find($tariffId);
                                                if (!$tariff || $tariff->billing_type !== 'daily') {
                                                    return false;
                                                }
                                                // Visible hanya jika checkbox range dicentang
                                                return $get('use_range_date') === true;
                                            }),
                                    ])
                                        ->columns(2)
                                        ->visible(function (callable $get) {
                                            $tariffId = $get('tariff_id');
                                            if (!$tariffId)
                                                return false;
                                            $tariff = \App\Models\Tariff::find($tariffId);
                                            return $tariff && $tariff->billing_type === 'daily';
                                        }),

                                ]),
                            ])
                            ->addActionLabel('Tambah Item Biaya'),
                    ]),

                /* STEP 3: KONFIGURASI & PREVIEW */
                Wizard\Step::make('Konfigurasi & Review')
                    ->description('Finalisasi tagihan')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(now()->addDays(7))
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('virtual_account_id')
                                ->label('Bank Pembayaran')
                                ->options(VirtualAccount::pluck('bank_name', 'id'))
                                ->required(),
                        ]),

                        Forms\Components\Section::make('Preview Ringkasan')
                            ->schema([
                                Forms\Components\Placeholder::make('review_summary')
                                    ->label('')
                                    ->content(function ($get) {
                                        $studentCount = count($get('student_ids') ?? []);
                                        $items = $get('tariff_items') ?? [];

                                        $totalPerSiswa = 0;
                                        foreach ($items as $item) {
                                            if (isset($item['tariff_id'])) {
                                                $totalPerSiswa += (float) (Tariff::find($item['tariff_id'])?->amount ?? 0);
                                            }
                                        }

                                        $grandTotal = $totalPerSiswa * $studentCount;

                                        return new HtmlString("
                                            <div class='space-y-2'>
                                                <p>Total Siswa: <strong>{$studentCount} Orang</strong></p>
                                                <p>Tagihan per Siswa: <strong>Rp " . number_format((float) $totalPerSiswa, 0, ',', '.') . "</strong></p>
                                                <div class='p-3 bg-primary-50 border border-primary-200 rounded-lg'>
                                                    <p class='text-sm text-primary-700'>Estimasi Total Piutang:</p>
                                                    <p class='text-2xl font-bold text-primary-800 font-mono'>Rp " . number_format((float) $grandTotal, 0, ',', '.') . "</p>
                                                </div>
                                            </div>
                                        ");
                                    }),
                            ]),
                    ]),
            ])->columnSpanFull()
        ]);
    }

    /* ===============================
     | VIEW FORM
     =============================== */
    private static function viewForm(Form $form): Form
    {
        $record = $form->getRecord();

        return $form->schema([
            Forms\Components\Section::make('Informasi Invoice')
                ->schema([
                    Forms\Components\Placeholder::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->content($record->invoice_number ?? '-'),

                    Forms\Components\Placeholder::make('student.name')
                        ->label('Siswa')
                        ->content($record->student->name ?? '-'),

                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(fn() => match ($record->status) {
                            'paid' => 'Lunas',
                            'unpaid' => 'Belum Lunas',
                            'cancelled' => 'Dibatalkan',
                            default => $record->status,
                        }),

                    Forms\Components\Placeholder::make('due_date')
                        ->label('Jatuh Tempo')
                        ->content($record->due_date?->format('d/m/Y') ?? '-'),

                    Forms\Components\Placeholder::make('total_amount')
                        ->label('Total Tagihan')
                        ->content('Rp ' . number_format((float) ($record->total_amount ?? 0), 0, ',', '.')),
                ])
                ->columns(2),

            Forms\Components\Section::make('Rincian Item')
                ->schema([
                    Forms\Components\Placeholder::make('items_list')
                        ->label('')
                        ->content(function ($record) {
                            $items = $record->items()->with('tariff')->get();
                            if ($items->isEmpty()) {
                                return 'Tidak ada item';
                            }

                            $monthNames = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];

                            // Kelompokkan items berdasarkan income_type dan billing_type untuk tampilan yang lebih rapi
                            $groupedItems = $items->groupBy(function ($item) {
                                $tariff = $item->tariff;
                                $incomeTypeName = $tariff->incomeType->name ?? 'Unknown';
                                $billingType = $tariff->billing_type ?? '';
                                return $incomeTypeName . '|' . $billingType;
                            });

                            $html = '<div class="space-y-3">';
                            foreach ($groupedItems as $groupKey => $groupItems) {
                                [$incomeTypeName, $billingType] = explode('|', $groupKey);
                                
                                $html .= "<div class='p-3 border rounded-lg bg-gray-50'>";
                                $html .= "<p class='font-semibold mb-2'>{$incomeTypeName}</p>";
                                
                                if ($billingType === 'monthly') {
                                    // Untuk monthly, tampilkan setiap item
                                    foreach ($groupItems as $item) {
                                        $monthName = $monthNames[$item->period_month] ?? $item->period_month;
                                        $html .= "<p class='text-sm text-gray-600 ml-2'>• {$monthName} {$item->period_year}</p>";
                                    }
                                } elseif (in_array($billingType, ['once', 'yearly'])) {
                                    // Untuk once/yearly, tampilkan tahun
                                    $years = $groupItems->pluck('period_year')->unique()->sort()->values();
                                    $html .= "<p class='text-sm text-gray-600 ml-2'>Tahun: " . $years->implode(', ') . "</p>";
                                } elseif ($billingType === 'daily') {
                                    // Untuk daily, kelompokkan tanggal yang berurutan menjadi range
                                    $dates = $groupItems->map(function ($item) {
                                        return $item->period_day 
                                            ? \Carbon\Carbon::parse($item->period_day)
                                            : null;
                                    })->filter()->sort()->values();
                                    
                                    if ($dates->isNotEmpty()) {
                                        $ranges = [];
                                        $startDate = $dates->first();
                                        $endDate = $dates->first();
                                        
                                        foreach ($dates as $date) {
                                            if ($date->diffInDays($endDate) <= 1) {
                                                // Tanggal berurutan, perpanjang range
                                                $endDate = $date;
                                            } else {
                                                // Tanggal tidak berurutan, simpan range sebelumnya
                                                if ($startDate->eq($endDate)) {
                                                    $ranges[] = $startDate->format('d/m/Y');
                                                } else {
                                                    $ranges[] = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                                                }
                                                $startDate = $date;
                                                $endDate = $date;
                                            }
                                        }
                                        
                                        // Tambahkan range terakhir
                                        if ($startDate->eq($endDate)) {
                                            $ranges[] = $startDate->format('d/m/Y');
                                        } else {
                                            $ranges[] = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                                        }
                                        
                                        $html .= "<p class='text-sm text-gray-600 ml-2'>Tanggal: " . implode(', ', $ranges) . "</p>";
                                    }
                                } else {
                                    // Fallback untuk billing_type lain
                                    foreach ($groupItems as $item) {
                                        if ($item->period_month) {
                                            $monthName = $monthNames[$item->period_month] ?? $item->period_month;
                                            $html .= "<p class='text-sm text-gray-600 ml-2'>• {$monthName} {$item->period_year}</p>";
                                        } elseif ($item->period_year) {
                                            $html .= "<p class='text-sm text-gray-600 ml-2'>• Tahun: {$item->period_year}</p>";
                                        }
                                    }
                                }
                                
                                // Tampilkan total untuk group ini
                                $groupTotal = $groupItems->sum('final_amount');
                                $html .= "<p class='text-sm font-semibold mt-2'>Total: Rp " . number_format((float) $groupTotal, 0, ',', '.') . "</p>";
                                $html .= "</div>";
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        }),
                ]),
        ]);
    }

    /* ===============================
     | TABLE (COMPACT)
     =============================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->limit(18)
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->limit(20)
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.activeClass.classRoom.code')
                    ->label('Kelas')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'unpaid',
                        'success' => 'paid',
                        'gray' => 'cancel',
                    ]),
            ])

            /* FILTER */
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'cancel' => 'Cancel',
                    ]),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable(),
            ])

            /* ACTION */
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Invoice $record) => $record->status === 'unpaid')
                    ->action(
                        fn(Invoice $record) =>
                        $record->update(['status' => 'paid'])
                    )
                    ->requiresConfirmation(),
            ])

            ->defaultSort('created_at', 'desc');
    }

    /* ===============================
     | PAGES
     =============================== */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}