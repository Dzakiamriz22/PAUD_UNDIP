<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('receipts.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->amount_paid > 0),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Livewire::make(
                \App\Livewire\ReceiptPreview::class,
                ['record' => $infolist->getRecord()]
            )
                ->key(fn ($record) => 'receipt-preview-' . $record->id)
                ->columnSpanFull(),
        ]);
    }
}