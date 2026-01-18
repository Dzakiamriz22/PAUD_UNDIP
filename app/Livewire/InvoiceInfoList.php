<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;

class InvoiceInfoList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedInvoiceId = null;

    protected $queryString = ['search'];

    protected $listeners = [
        'refreshInvoices' => '$refresh',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectInvoice($id)
    {
        $this->selectedInvoiceId = $id;
    }

    public function getSelectedInvoiceProperty()
    {
        if (! $this->selectedInvoiceId) {
            return null;
        }

        return Invoice::with(['student', 'items', 'incomeType', 'academicYear', 'class'])->find($this->selectedInvoiceId);
    }

    public function render()
    {
        $query = Invoice::with(['student', 'items'])
            ->orderBy('issued_at', 'desc');

        if ($this->search) {
            $query->where('invoice_number', 'like', '%'.$this->search.'%');
        }

        $invoices = $query->paginate(10);

        return view('livewire.invoice-info-list', [
            'invoices' => $invoices,
        ]);
    }
}
