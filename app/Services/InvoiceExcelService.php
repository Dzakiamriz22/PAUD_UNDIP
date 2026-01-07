<?php

namespace App\Services;

use App\Enums\ProductTypeEnum;
use App\Models\Invoice;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceExcelService
{
    protected $templatePath;
    
    public function __construct()
    {
        $this->templatePath = storage_path('app/templates/invoice_template.xlsx');
    }
    
    /**
     * Generate Excel invoice from template with invoice data
     */
    public function generateInvoiceExcel(Invoice $invoice): string
    {
        // Load the template
        $spreadsheet = IOFactory::load($this->templatePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Load invoice with relationships
        $invoice = $invoice->load([
            'bookingOrder.user',
            'bookingOrder.venue',
            'bookingOrder.venue.gedung',
            'items',
            'groups'
        ]);
        
        // Populate invoice header data
        $this->populateInvoiceHeader($worksheet, $invoice);
        
        // Populate invoice items
        $this->populateInvoiceItems($worksheet, $invoice);
        
        // Populate tax calculations
        $this->populateTaxCalculations($worksheet, $invoice);
        
        // Generate filename and save
        $filename = 'invoice_' . $invoice->invoice_number . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filepath = storage_path('app/invoices/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return $filepath;
    }
    
    /**
     * Populate invoice header information
     */
    protected function populateInvoiceHeader($worksheet, Invoice $invoice): void
    {
        // Invoice number
        $worksheet->setCellValue('C11', $invoice->invoice_number);
        
        // Invoice date
        $worksheet->setCellValue('C12', $invoice->issued_at ? $invoice->issued_at->format('d/m/Y') : '');
        
        // Due date
        $worksheet->setCellValue('L59', $invoice->due_at ? $invoice->due_at->format('d/m/Y') : '');
        
        // Partner information
        if ($invoice->bookingOrder && $invoice->bookingOrder->user) {
            $worksheet->setCellValue('F16', $invoice->bookingOrder->user->name);
            $worksheet->setCellValue('F19', $invoice->bookingOrder->user->email ?? '');
        }
        
        // Venue information
        if ($invoice->bookingOrder && $invoice->bookingOrder->venue) {
            $worksheet->setCellValue('D30', $invoice->bookingOrder->venue->venue_name);
            if ($invoice->bookingOrder->venue->gedung) {
                $worksheet->setCellValue('I30', $invoice->bookingOrder->venue->gedung->lokasi);
            }
            // $worksheet->setCellValue('B8', $invoice->bookingOrder->venue->venue_code);
        }
        
        // Activity details
        if ($invoice->bookingOrder) {
            $worksheet->setCellValue('F27', $invoice->bookingOrder->activity_details ?? '');
        }
        
        // Virtual account
        $worksheet->setCellValue('L58', $invoice->virtual_account ?? '');
        
        // Status
        // $worksheet->setCellValue('B11', ucfirst($invoice->status));
        
        // Notes
        // $worksheet->setCellValue('B12', $invoice->notes ?? '');
    }
    
    /**
     * Populate invoice items
     */
        protected function populateInvoiceItems($worksheet, Invoice $invoice): void
        {
            $venueStartRow = 30; // Starting row for venue items
            $addonStartRow = 41; // Starting row for addon items
            
            $venueIndex = 0;
            $addonIndex = 0;
            
            foreach ($invoice->items as $item) {
                // Check if item is venue or addon based on description or item type
                // Adjust this condition based on your actual data structure
                $isAddon = stripos($item->description, 'addon') !== false || 
                        (isset($item->type) && $item->type === ProductTypeEnum::ADDON->value) ||
                        (isset($item->item_type) && $item->item_type === ProductTypeEnum::ADDON->value);
                
                if ($isAddon) {
                    // Addon items start from row 41
                    $row = $addonStartRow + $addonIndex;
                    $number = $addonIndex + 1;
                    $addonIndex++;
                } else {
                    // Venue items start from row 30
                    $row = $venueStartRow + $venueIndex;
                    $number = $venueIndex + 1;
                    $venueIndex++;
                }
                
                // Number column - as number
                $worksheet->setCellValue('C' . $row, $number);
                
                // Description - as text
                $worksheet->setCellValue('D' . $row, $item->description);
                
                // Quantity - as number
                $worksheet->setCellValue('H' . $row, (float) $item->quantity);
                
                // Dates - as text
                if ($invoice->bookingOrder) {
                    if ($invoice->bookingOrder->start_date_request) {
                        $startDate = \Carbon\Carbon::parse($invoice->bookingOrder->start_date_request)
                            ->locale('id')
                            ->translatedFormat('d F Y');
                        $worksheet->setCellValue('J' . $row, $startDate);
                    }
                    if ($invoice->bookingOrder->end_date_request) {
                        $endDate = \Carbon\Carbon::parse($invoice->bookingOrder->end_date_request)
                            ->locale('id')
                            ->translatedFormat('d F Y');
                        $worksheet->setCellValue('K' . $row, $endDate);
                    }
                }
                
                // Unit price - as number with accounting format
                $worksheet->setCellValue('L' . $row, (float) $item->unit_price);
                $worksheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');
                
                // Total price - as number with accounting format
                $worksheet->setCellValue('O' . $row, (float) $item->total_price);
                $worksheet->getStyle('O' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
        }
    
    /**
     * Populate tax calculations
     */
    protected function populateTaxCalculations($worksheet, Invoice $invoice): void
    {
        // Separate groups by type
        $venueGroup = $invoice->groups->where('type', ProductTypeEnum::VENUE->value)->first();
        $addonGroup = $invoice->groups->where('type', ProductTypeEnum::ADDON->value)->first();
        
        // Populate VENUE calculations (rows 33-37)
        if ($venueGroup) {
            // Subtotal - as number
            $worksheet->setCellValue('O33', (float) $venueGroup->subtotal);
            $worksheet->getStyle('O33')->getNumberFormat()->setFormatCode('#,##0');
            
            // Discount - as number
            $worksheet->setCellValue('O34', (float) $venueGroup->discount);
            $worksheet->getStyle('O34')->getNumberFormat()->setFormatCode('#,##0');
            
            // Gross total - as number
            $worksheet->setCellValue('O35', (float) $venueGroup->gross_total);
            $worksheet->getStyle('O35')->getNumberFormat()->setFormatCode('#,##0');
            
            // Grand total - as number
            $worksheet->setCellValue('O37', (float) $venueGroup->grand_total);
            $worksheet->getStyle('O37')->getNumberFormat()->setFormatCode('#,##0');
        } else {
            // Clear venue section if no venue group
            $worksheet->setCellValue('O33', 0);
            $worksheet->getStyle('O33')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O34', 0);
            $worksheet->getStyle('O34')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O35', 0);
            $worksheet->getStyle('O35')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O37', 0);
            $worksheet->getStyle('O37')->getNumberFormat()->setFormatCode('#,##0');
        }
        
        // Populate ADDON calculations (rows 41-45, sesuaikan dengan template Anda)
        if ($addonGroup) {
            // Subtotal - as number
            $worksheet->setCellValue('O46', (float) $addonGroup->subtotal);
            $worksheet->getStyle('O46')->getNumberFormat()->setFormatCode('#,##0');
            
            // Discount - as number
            $worksheet->setCellValue('O47', (float) $addonGroup->discount);
            $worksheet->getStyle('O47')->getNumberFormat()->setFormatCode('#,##0');
            
            // Gross total - as number
            $worksheet->setCellValue('O48', (float) $addonGroup->gross_total);
            $worksheet->getStyle('O48')->getNumberFormat()->setFormatCode('#,##0');
            
            // Grand total - as number
            $worksheet->setCellValue('O50', (float) $addonGroup->grand_total);
            $worksheet->getStyle('O50')->getNumberFormat()->setFormatCode('#,##0');
        } else {
            // Clear addon section if no addon group
            $worksheet->setCellValue('O46', 0);
            $worksheet->getStyle('O46')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O47', 0);
            $worksheet->getStyle('O47')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O48', 0);
            $worksheet->getStyle('O48')->getNumberFormat()->setFormatCode('#,##0');
            
            $worksheet->setCellValue('O50', 0);
            $worksheet->getStyle('O50')->getNumberFormat()->setFormatCode('#,##0');
        }
    }
    
    /**
     * Get download URL for generated invoice
     */
    public function getDownloadUrl(string $filepath): string
    {
        $filename = basename($filepath);
        return route('invoice.download', ['filename' => $filename]);
    }
}