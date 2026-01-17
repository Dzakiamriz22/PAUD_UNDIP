<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Invoice';
    protected static ?int $navigationSort = 1;

    /* ===============================
     | VIEW FORM (READ ONLY)
     =============================== */
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* INFORMASI INVOICE */
            Forms\Components\Section::make('Informasi Invoice')
                ->schema([

                    Placeholder::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->content(fn (Invoice $record) => $record->invoice_number),

                    Placeholder::make('student')
                        ->label('Siswa')
                        ->content(fn (Invoice $record) => $record->student?->name ?? '-'),

                    Placeholder::make('class')
                        ->label('Kelas')
                        ->content(fn (Invoice $record) => $record->class?->code ?? '-'),

                    Placeholder::make('academic_year')
                        ->label('Tahun Ajaran')
                        ->content(fn (Invoice $record) =>
                            $record->academicYear?->label ?? '-'
                        ),

                    Placeholder::make('income_type')
                        ->label('Jenis Pembayaran')
                        ->content(fn (Invoice $record) =>
                            $record->incomeType?->name ?? '-'
                        ),
                ])
                ->columns(2),


            /* PEMBAYARAN */
            Forms\Components\Section::make('Pembayaran')
                ->schema([

                    Placeholder::make('va_number')
                        ->label('VA Number')
                        ->content(fn (Invoice $record) => $record->va_number),

                    Placeholder::make('va_bank')
                        ->label('Bank')
                        ->content(fn (Invoice $record) => $record->va_bank),

                    Placeholder::make('due_date')
                        ->label('Jatuh Tempo')
                        ->content(fn (Invoice $record) =>
                            optional($record->due_date)->format('d/m/Y')
                        ),

                    Placeholder::make('status')
                        ->label('Status')
                        ->content(fn (Invoice $record) =>
                            strtoupper($record->status)
                        ),
                ])
                ->columns(2),

            /* DETAIL TARIF */
            Forms\Components\Section::make('Detail Tarif')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->disabled()
                        ->schema([

                            Placeholder::make('description')
                                ->label('Nama Tarif')
                                ->content(fn ($state) => $state),

                            Placeholder::make('final_amount')
                                ->label('Nominal')
                                ->content(fn ($state) =>
                                    'Rp ' . number_format($state, 0, ',', '.')
                                ),
                        ])
                        ->columns(2),
                ]),

            /* TOTAL */
            Forms\Components\Section::make('Total')
                ->schema([
                    Placeholder::make('total_amount')
                        ->label('Total Tagihan')
                        ->content(fn (Invoice $record) =>
                            'Rp ' . number_format($record->total_amount, 0, ',', '.')
                        ),
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

                Tables\Columns\TextColumn::make('class.code')
                    ->label('Kelas')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger'  => 'unpaid',
                        'success' => 'paid',
                        'gray'    => 'cancel',
                    ]),
            ])

            /* FILTER */
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid'   => 'Paid',
                        'cancel' => 'Cancel',
                    ]),

                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('class', 'code'),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('income_type_id')
                    ->label('Jenis Pembayaran')
                    ->relationship('incomeType', 'name'),
            ])

            /* ACTION */
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status === 'unpaid')
                    ->action(fn (Invoice $record) =>
                        $record->update(['status' => 'paid'])
                    )
                    ->requiresConfirmation(),
            ])

            ->defaultSort('created_at', 'desc');
    }

    /* ===============================
     | PAGES (NO EDIT / CREATE)
     =============================== */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view'  => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}