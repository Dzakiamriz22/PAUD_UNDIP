<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\BatchInvoiceService;
use Illuminate\Http\Request;
use ZipArchive;

class InvoiceBatchDownloadController extends Controller
{
    public function download(Request $request, BatchInvoiceService $service)
    {
        $invoiceIds = $request->input('invoice_ids', []);

        abort_if(empty($invoiceIds), 400, 'Invoice tidak dipilih');

        $invoices = Invoice::with('student')->whereIn('id', $invoiceIds)->get();
        abort_if($invoices->isEmpty(), 404);

        $tmpDir = storage_path('app/invoice_tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // 👉 Jika hanya 1 invoice → langsung PDF
        if ($invoices->count() === 1) {
            $invoice = $invoices->first();
            return $service
                ->generateSinglePdf($invoice)
                ->download('invoice-' . str_replace('/', '-', $invoice->invoice_number) . '.pdf');
        }

        // 👉 Banyak invoice → ZIP
        $zipName = 'invoice-batch-' . now()->format('Ymd-His') . '.zip';
        $zipPath = $tmpDir . '/' . $zipName;

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($invoices as $invoice) {
            $pdf = $service->generateSinglePdf($invoice);

            $safeNumber = str_replace('/', '-', $invoice->invoice_number);
            $studentName = preg_replace('/[^A-Za-z0-9\-_. ]/', '', $invoice->student->name ?? 'student');

            $filename = "invoice-{$safeNumber}-{$studentName}.pdf";

            $zip->addFromString($filename, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}