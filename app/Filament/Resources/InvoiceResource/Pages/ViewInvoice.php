<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->downloadPdf()),
        ];
    }

    protected function downloadPdf()
    {
        $invoice = $this->record->load([
            'student.activeClass.classRoom',
            'academicYear',
            'items',
        ]);

        // Use the invoice-doc view for consistent page/pdf appearance
        $pdf = Pdf::loadView('pdf.invoice-single', compact('invoice'));

        // Hilangkan slash agar aman untuk nama file
        $safeInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'invoice-' . $safeInvoiceNumber . '.pdf'
        );
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Livewire::make(
                \App\Livewire\InvoicePreview::class,
                [
                    'invoice' => $infolist->getRecord(),
                ]
            )
                ->key(fn ($record) => 'invoice-preview-' . $record->id)
                ->columnSpanFull(),
        ]);
    }
}
