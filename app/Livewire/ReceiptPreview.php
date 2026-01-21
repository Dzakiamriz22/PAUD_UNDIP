<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Receipt;

class ReceiptPreview extends Component
{
    public Receipt $receipt;

    public function mount(Receipt $record): void
    {
        $this->receipt = $record->load([
            'invoice.student.activeClass.classRoom',
            'invoice.items.tariff.incomeType',
            'invoice.academicYear',
            'creator',
        ]);
    }

    public function render()
    {
        return view('livewire.receipt-preview', [
            'receipt' => $this->receipt,
        ]);
    }
}