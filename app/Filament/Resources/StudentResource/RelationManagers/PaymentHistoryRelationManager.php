<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceItems';

    protected static ?string $title = 'Riwayat Pembayaran';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->toggleable(),

                // Tables\Columns\TextColumn::make('tariff.incomeType.name')
                //     ->label('Komponen Biaya / Jenis Pendapatan')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('tariff.billing_type')
                    ->label('Jenis Pembayaran')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'once' => 'Sekali Bayar',
                        'monthly' => 'Bulanan',
                        'yearly' => 'Tahunan',
                        'daily' => 'Harian',
                        'penalty' => 'Denda',
                        default => $state,
                    })
                    ->toggleable(),

                

                // Tables\Columns\TextColumn::make('period_month')
                //     ->label('Bulan')
                //     ->formatStateUsing(fn ($state) => match($state) {
                //         1 => 'Januari',
                //         2 => 'Februari',
                //         3 => 'Maret',
                //         4 => 'April',
                //         5 => 'Mei',
                //         6 => 'Juni',
                //         7 => 'Juli',
                //         8 => 'Agustus',
                //         9 => 'September',
                //         10 => 'Oktober',
                //         11 => 'November',
                //         12 => 'Desember',
                //         default => '-',
                //     })
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('period_year')
                //     ->label('Tahun')
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('period_day')
                //     ->label('Tanggal')
                //     ->date('d/m/Y')
                //     ->toggleable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('invoice.status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'unpaid' => 'Belum Lunas',
                        'draft' => 'Draft',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'paid',
                        'danger' => ['unpaid', 'cancelled'],
                        'gray' => 'draft',
                    ]),

                Tables\Columns\TextColumn::make('invoice.paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('invoice_status')
                    ->label('Status Pembayaran')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'paid' => 'Lunas',
                                'unpaid' => 'Belum Lunas',
                                'draft' => 'Draft',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('paid'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['status'] ?? 'paid';
                        return $query->whereHas('invoice', function ($q) use ($status) {
                            $q->where('status', $status);
                        });
                    })
                    ->default(['status' => 'paid']),

                Tables\Filters\Filter::make('period_year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = $this->ownerRecord->invoiceItems()
                                    ->distinct()
                                    ->pluck('period_year')
                                    ->sortDesc()
                                    ->mapWithKeys(fn ($year) => [$year => $year]);
                                return $years->toArray();
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->where('period_year', $year),
                            );
                    }),

                Tables\Filters\Filter::make('period_month')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
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
                                12 => 'Desember',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn (Builder $query, $month): Builder => $query->where('period_month', $month),
                            );
                    }),
            ])
            ->defaultSort('period_year', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load relasi untuk performa optimal
                return $query
                    ->with(['invoice', 'tariff.incomeType'])
                    ->orderBy('period_year', 'desc')
                    ->orderBy('period_month', 'desc');
            });
    }
}

