<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;

class InvoicePreview extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load([
            'student.activeClass.classRoom',
            'items.tariff.incomeType',
            'academicYear',
            'creator',
        ]);
    }

    /**
     * Helper: cek invoice lunas
     */
    public function isPaid(): bool
    {
        return !is_null($this->invoice->paid_at)
            || ($this->invoice->status === 'paid');
    }

    /**
     * Helper: label status invoice
     */
    public function statusLabel(): string
    {
        return $this->isPaid()
            ? 'LUNAS'
            : strtoupper($this->invoice->status ?? 'MENUNGGU');
    }

    /**
     * Helper: class warna status
     */
    public function statusClass(): string
    {
        return $this->isPaid()
            ? 'text-green-600'
            : 'text-yellow-600';
    }

    public function render()
    {
        return view('livewire.invoice-preview', [
            'invoice' => $this->invoice,
            'statusLabel' => $this->statusLabel(),
            'statusClass' => $this->statusClass(),
            'isPaid' => $this->isPaid(),
        ]);
    }
}