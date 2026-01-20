<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPdfController extends Controller
{
    protected function loadReceipt(Receipt $receipt): Receipt
    {
        return $receipt->load([
            'invoice.student',
            'invoice.items.tariff.incomeType',
            'creator',
        ]);
    }

    public function preview(Receipt $receipt)
    {
        $receipt = $this->loadReceipt($receipt);

        return view('receipts.pdf', compact('receipt'));
    }

    public function download(Receipt $receipt)
    {
        $receipt = $this->loadReceipt($receipt);

        return Pdf::loadView('receipts.pdf', compact('receipt'))
            ->setPaper('A4')
            ->download(
                'kuitansi-' . str_replace('/', '-', $receipt->receipt_number) . '.pdf'
            );
    }
}