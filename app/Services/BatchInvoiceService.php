<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class BatchInvoiceService
{
    /**
     * Generate PDF banyak invoice (1 file)
     */
    public function generateBatchPdf(Collection $invoices)
    {
        $invoices->load([
            'student',
            'items',
            'student.activeClass.classRoom',
        ]);

        return Pdf::loadView('pdf.batch-invoice', [
            'invoices' => $invoices,
        ])->setPaper('A4', 'portrait');
    }

    /**
     * Generate PDF satu invoice
     */
    public function generateSinglePdf(Invoice $invoice)
    {
        $invoice->load([
            'student',
            'items',
            'student.activeClass.classRoom',
        ]);

        return Pdf::loadView('pdf.invoice-single', [
            'invoice' => $invoice,
        ])->setPaper('A4', 'portrait');
    }
}
