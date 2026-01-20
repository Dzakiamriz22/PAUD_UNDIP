<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Receipt;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Kuitansi';
    protected static ?string $pluralLabel = 'Kuitansi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Invoice')
                    ->schema([
                        Select::make('invoice_id')
                            ->label('Invoice')
                            ->options(function ($record) {
                                // Saat edit, tampilkan invoice yang sudah dipilih
                                if ($record && $record->invoice_id) {
                                    $currentInvoice = Invoice::with('student')->find($record->invoice_id);
                                    if ($currentInvoice) {
                                        return [
                                            $currentInvoice->id => $currentInvoice->invoice_number . ' - ' . ($currentInvoice->student->name ?? 'N/A')
                                        ];
                                    }
                                }
                                
                                // Saat create, tampilkan invoice yang belum punya receipt
                                return Invoice::with('student')
                                    ->whereDoesntHave('receipt')
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        return [$invoice->id => $invoice->invoice_number . ' - ' . ($invoice->student->name ?? 'N/A')];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled(fn ($record) => $record !== null || request()->query('invoice_id') !== null) // Disable saat edit atau jika invoice_id di-query string
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    if ($invoice) {
                                        // Cek apakah invoice sudah punya receipt
                                        if ($invoice->receipt) {
                                            \Filament\Notifications\Notification::make()
                                                ->warning()
                                                ->title('Invoice sudah memiliki kuitansi')
                                                ->body('Invoice ini sudah memiliki kuitansi. Pilih invoice lain.')
                                                ->send();
                                            $set('invoice_id', null);
                                            return;
                                        }

                                        // Set default amount_paid sama dengan total_amount invoice
                                        $set('amount_paid', $invoice->total_amount);
                                        // Jika invoice punya VA, set metode pembayaran dan nomor referensi otomatis
                                        if (!empty($invoice->va_number)) {
                                            $set('payment_method', 'va');
                                            $set('reference_number', $invoice->va_number);
                                        }

                                        // Set issued_at ke waktu sekarang
                                        $set('issued_at', now());
                                    }
                                }
                            })
                            ->helperText(fn ($record) => $record ? 'Invoice tidak dapat diubah setelah kuitansi dibuat' : 'Invoice yang akan dibuat'),

                        Forms\Components\Placeholder::make('invoice_info')
                            ->label('')
                            ->content(function (callable $get, $record) {
                                $invoiceId = $get('invoice_id') ?? $record?->invoice_id;
                                if (!$invoiceId) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Pilih invoice untuk melihat detail</p>');
                                }

                                $invoice = Invoice::with('student')->find($invoiceId);
                                if (!$invoice) {
                                    return new HtmlString('<p class="text-sm text-red-500">Invoice tidak ditemukan</p>');
                                }

                                $studentName = $invoice->student->name ?? '-';
                                $totalAmount = 'Rp ' . number_format((float) ($invoice->total_amount ?? 0), 0, ',', '.');

                                return new HtmlString("
                                    <div class='p-3 bg-gray-50 rounded-lg border border-gray-200'>
                                        <p class='text-sm font-semibold text-gray-700 mb-1'>Siswa: {$studentName}</p>
                                        <p class='text-sm text-gray-600'>Total Tagihan: <span class='font-semibold'>{$totalAmount}</span></p>
                                    </div>
                                ");
                            })
                            ->visible(fn (callable $get, $record) => !empty($get('invoice_id') ?? $record?->invoice_id)),
                    ]),

                Section::make('Detail Pembayaran')
                    ->schema([
                        Forms\Components\Placeholder::make('receipt_number_info')
                            ->label('Nomor Kuitansi')
                            ->content('Nomor kuitansi akan otomatis dibuat saat menyimpan')
                            ->visible(fn ($record) => $record === null), // Hanya tampilkan saat create
                        
                        TextInput::make('receipt_number')
                            ->label('Nomor Kuitansi')
                            ->disabled()
                            ->dehydrated(false) // Jangan kirim ke database, biarkan booted() yang generate
                            ->default(fn ($record) => $record?->receipt_number ?? '')
                            ->visible(fn ($record) => $record !== null), // Hanya tampilkan saat edit

                        TextInput::make('amount_paid')
                            ->label('Jumlah Dibayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'bank_transfer' => 'Transfer Bank',
                                'va' => 'Virtual Account',
                                'qris' => 'QRIS',
                                'other' => 'Lainnya',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('reference_number')
                            ->label('Nomor Referensi')
                            ->placeholder('Nomor transaksi VA, referensi bank, dll.')
                            ->maxLength(255),

                        DateTimePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        DateTimePicker::make('issued_at')
                            ->label('Tanggal Diterbitkan')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Textarea::make('note')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Nomor Kuitansi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Jumlah Dibayar')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignEnd(),

                BadgeColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'cash' => 'Tunai',
                        'bank_transfer' => 'Transfer Bank',
                        'va' => 'Virtual Account',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'bank_transfer',
                        'warning' => 'va',
                        'info' => 'qris',
                        'gray' => 'other',
                    ]),

                TextColumn::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('creator.username')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'bank_transfer' => 'Transfer Bank',
                        'va' => 'Virtual Account',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ]),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipt::route('/create'),
            'view' => Pages\ViewReceipt::route('/{record}'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }
}

