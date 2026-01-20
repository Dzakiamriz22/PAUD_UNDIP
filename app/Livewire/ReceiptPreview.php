<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Receipt;

class ReceiptPreview extends Component
{
    public Receipt $record;

    public function mount(Receipt $record): void
    {
        $this->record = $record->load([
            'invoice.student',
            'invoice.items.tariff.incomeType',
            'creator',
        ]);
    }

    public function render()
    {
        return view('livewire.receipt-preview', [
            'receipt' => $this->record,
        ]);
    }
}
