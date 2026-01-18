<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Invoice')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Nomor Invoice'),

                        Infolists\Components\TextEntry::make('invoice.student.name')
                            ->label('Siswa'),

                        Infolists\Components\TextEntry::make('invoice.total_amount')
                            ->label('Total Tagihan')
                            ->money('IDR', locale: 'id'),

                        Infolists\Components\TextEntry::make('invoice.status')
                            ->label('Status Invoice')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'paid' => 'Lunas',
                                'unpaid' => 'Belum Lunas',
                                'draft' => 'Draft',
                                'cancelled' => 'Dibatalkan',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid' => 'success',
                                'unpaid' => 'danger',
                                'draft' => 'gray',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Detail Kuitansi')
                    ->schema([
                        Infolists\Components\TextEntry::make('receipt_number')
                            ->label('Nomor Kuitansi'),

                        Infolists\Components\TextEntry::make('amount_paid')
                            ->label('Jumlah Dibayar')
                            ->money('IDR', locale: 'id'),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'cash' => 'Tunai',
                                'bank_transfer' => 'Transfer Bank',
                                'va' => 'Virtual Account',
                                'qris' => 'QRIS',
                                'other' => 'Lainnya',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'cash' => 'success',
                                'bank_transfer' => 'primary',
                                'va' => 'warning',
                                'qris' => 'info',
                                'other' => 'gray',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('reference_number')
                            ->label('Nomor Referensi')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('issued_at')
                            ->label('Tanggal Diterbitkan')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('creator.username')
                            ->label('Dibuat Oleh'),

                        Infolists\Components\TextEntry::make('note')
                            ->label('Catatan')
                            ->default('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

