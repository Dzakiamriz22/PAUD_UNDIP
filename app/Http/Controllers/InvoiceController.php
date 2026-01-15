use App\Models\Invoice;
use App\Services\BatchInvoiceService;

public function downloadSingleInvoice(
    Invoice $invoice,
    BatchInvoiceService $service
) {
    return $service
        ->generateSinglePdf($invoice)
        ->download('invoice-'.$invoice->invoice_number.'.pdf');
}

public function downloadBatchInvoice(
    BatchInvoiceService $service
) {
    $invoices = Invoice::where('status', 'unpaid')->get();

    return $service
        ->generateBatchPdf($invoices)
        ->download('invoice-batch.pdf');
}
