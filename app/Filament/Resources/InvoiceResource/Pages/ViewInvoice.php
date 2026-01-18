<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewPdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->url(route('invoices.preview', $this->record))
                ->openUrlInNewTab()
                ->color('primary'),
        ];
    }

    protected function downloadPdf()
    {
        $invoice = $this->record->load([
            'student.activeClass.classRoom',
            'academicYear',
            'incomeType',
            'items',
        ]);

        $pdf = Pdf::loadView(
            'pdf.invoice-single',
            compact('invoice')
        );

        // AMAN: hilangkan slash agar tidak dianggap folder
        $safeInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'invoice-' . $safeInvoiceNumber . '.pdf'
        );
    }
}
