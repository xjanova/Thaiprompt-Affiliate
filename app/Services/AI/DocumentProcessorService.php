<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentProcessorService
{
    /**
     * Process document based on type
     *
     * @param string $type Document type (text, pdf, docx, url, file)
     * @param mixed $source Source content or path
     * @return array ['success' => bool, 'content' => string, 'metadata' => array]
     */
    public function processDocument(string $type, $source): array
    {
        try {
            return match ($type) {
                'text' => $this->processText($source),
                'pdf' => $this->processPdf($source),
                'docx' => $this->processDocx($source),
                'url' => $this->processUrl($source),
                'file' => $this->processFile($source),
                default => [
                    'success' => false,
                    'error' => 'Unsupported document type: ' . $type,
                ],
            };
        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process plain text
     */
    private function processText(string $text): array
    {
        $cleaned = $this->cleanText($text);

        return [
            'success' => true,
            'content' => $cleaned,
            'metadata' => [
                'type' => 'text',
                'length' => mb_strlen($cleaned),
                'word_count' => str_word_count($cleaned, 0, 'ก-๙'),
            ],
        ];
    }

    /**
     * Process PDF file
     */
    private function processPdf(string $filePath): array
    {
        // Check if PDF parser is available
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            return [
                'success' => false,
                'error' => 'PDF parser not installed. Run: composer require smalot/pdfparser',
            ];
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);

            $text = $pdf->getText();
            $cleaned = $this->cleanText($text);

            $pages = $pdf->getPages();
            $metadata = [
                'type' => 'pdf',
                'pages' => count($pages),
                'length' => mb_strlen($cleaned),
                'word_count' => str_word_count($cleaned, 0, 'ก-๙'),
            ];

            // Try to extract metadata
            $details = $pdf->getDetails();
            if (isset($details['Title'])) {
                $metadata['title'] = $details['Title'];
            }
            if (isset($details['Author'])) {
                $metadata['author'] = $details['Author'];
            }

            return [
                'success' => true,
                'content' => $cleaned,
                'metadata' => $metadata,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'PDF parsing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process DOCX file
     */
    private function processDocx(string $filePath): array
    {
        // Check if PHPWord is available
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            return [
                'success' => false,
                'error' => 'PHPWord not installed. Run: composer require phpoffice/phpword',
            ];
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $text = '';

            // Extract text from all sections
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromElement($element) . "\n\n";
                }
            }

            $cleaned = $this->cleanText($text);

            return [
                'success' => true,
                'content' => $cleaned,
                'metadata' => [
                    'type' => 'docx',
                    'length' => mb_strlen($cleaned),
                    'word_count' => str_word_count($cleaned, 0, 'ก-๙'),
                    'sections' => count($phpWord->getSections()),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'DOCX parsing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract text from PHPWord element
     */
    private function extractTextFromElement($element): string
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $text = $element->getText();
        } elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
        }

        return $text;
    }

    /**
     * Process URL (web scraping)
     */
    private function processUrl(string $url): array
    {
        try {
            // Fetch URL content
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; KnowledgeBaseBot/1.0)',
                ])
                ->get($url);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch URL: HTTP ' . $response->status(),
                ];
            }

            $html = $response->body();

            // Extract text from HTML
            $text = $this->extractTextFromHtml($html);
            $cleaned = $this->cleanText($text);

            return [
                'success' => true,
                'content' => $cleaned,
                'metadata' => [
                    'type' => 'url',
                    'url' => $url,
                    'length' => mb_strlen($cleaned),
                    'word_count' => str_word_count($cleaned, 0, 'ก-๙'),
                    'fetched_at' => now()->toIso8601String(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'URL processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract text from HTML
     */
    private function extractTextFromHtml(string $html): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Remove HTML comments
        $html = preg_replace('/<!--(.*)-->/Uis', '', $html);

        // Convert to plain text
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }

    /**
     * Process uploaded file (auto-detect type)
     */
    private function processFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found: ' . $filePath,
            ];
        }

        // Detect file type by extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->processPdf($filePath),
            'docx', 'doc' => $this->processDocx($filePath),
            'txt', 'md' => $this->processText(file_get_contents($filePath)),
            default => [
                'success' => false,
                'error' => 'Unsupported file type: ' . $extension,
            ],
        };
    }

    /**
     * Clean and normalize text
     */
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove excessive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Validate URL format
     */
    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get supported file extensions
     */
    public static function getSupportedExtensions(): array
    {
        return ['pdf', 'docx', 'doc', 'txt', 'md'];
    }

    /**
     * Get max file size (in bytes)
     */
    public static function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }
}
