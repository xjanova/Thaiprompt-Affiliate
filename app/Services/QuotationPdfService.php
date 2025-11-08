<?php

namespace App\Services;

use App\Models\SoftwareQuotation;
use Illuminate\Support\Facades\View;

class QuotationPdfService
{
    /**
     * Generate PDF for quotation
     *
     * Note: This service requires DomPDF package
     * Install with: composer require barryvdh/laravel-dompdf
     *
     * If DomPDF is not installed, use generateHtml() method instead
     * to get HTML that can be converted to PDF by browser or other tools
     */
    public function generatePdf(SoftwareQuotation $quotation): string
    {
        // Check if DomPDF is available
        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            throw new \Exception('DomPDF is not installed. Please install it with: composer require barryvdh/laravel-dompdf');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation->load(['items.selectedOptions', 'softwareProduct']),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate HTML for quotation (can be used with browser print or wkhtmltopdf)
     */
    public function generateHtml(SoftwareQuotation $quotation): string
    {
        return View::make('pdf.quotation', [
            'quotation' => $quotation->load(['items.selectedOptions', 'softwareProduct']),
        ])->render();
    }

    /**
     * Download PDF
     */
    public function download(SoftwareQuotation $quotation, string $filename = null): \Symfony\Component\HttpFoundation\Response
    {
        if (!$filename) {
            $filename = 'quotation-' . $quotation->quotation_number . '.pdf';
        }

        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            // Fallback: Return HTML with print dialog
            $html = $this->generateHtml($quotation);
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '.html"');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation->load(['items.selectedOptions', 'softwareProduct']),
        ]);

        return $pdf->download($filename);
    }

    /**
     * Stream PDF to browser
     */
    public function stream(SoftwareQuotation $quotation): \Symfony\Component\HttpFoundation\Response
    {
        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            // Fallback: Return HTML
            $html = $this->generateHtml($quotation);
            return response($html)->header('Content-Type', 'text/html');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation->load(['items.selectedOptions', 'softwareProduct']),
        ]);

        return $pdf->stream('quotation-' . $quotation->quotation_number . '.pdf');
    }

    /**
     * Save PDF to storage
     */
    public function save(SoftwareQuotation $quotation, string $path): bool
    {
        try {
            $pdfContent = $this->generatePdf($quotation);
            \Illuminate\Support\Facades\Storage::put($path, $pdfContent);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save PDF: ' . $e->getMessage());
            return false;
        }
    }
}
