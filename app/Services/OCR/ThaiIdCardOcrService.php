<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;

class ThaiIdCardOcrService
{
    protected $client;

    public function __construct()
    {
        // Initialize Google Cloud Vision client if credentials are available
        if ($this->hasGoogleCredentials()) {
            try {
                $this->client = new ImageAnnotatorClient([
                    'credentials' => config('services.google.credentials_path')
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to initialize Google Vision API: ' . $e->getMessage());
                $this->client = null;
            }
        }
    }

    /**
     * Extract data from Thai ID card image
     *
     * @param string $imagePath Path to the image file
     * @return array|null Extracted data or null if extraction failed
     */
    public function extractData(string $imagePath): ?array
    {
        try {
            // Read the image file
            $imageContent = Storage::disk('public')->get($imagePath);

            // Try Google Cloud Vision API first
            if ($this->client) {
                return $this->extractWithGoogleVision($imageContent);
            }

            // Fallback to pattern matching OCR
            return $this->extractWithPatternMatching($imageContent);
        } catch (\Exception $e) {
            Log::error('Thai ID Card OCR Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract data using Google Cloud Vision API
     */
    protected function extractWithGoogleVision(string $imageContent): ?array
    {
        try {
            $image = new Image();
            $image->setContent($imageContent);

            // Perform text detection
            $response = $this->client->textDetection($image);
            $texts = $response->getTextAnnotations();

            if (count($texts) === 0) {
                return null;
            }

            // Get all detected text
            $fullText = $texts[0]->getDescription();

            // Parse the text to extract ID card information
            return $this->parseThaiIdCardText($fullText);
        } catch (\Exception $e) {
            Log::error('Google Vision API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract data using pattern matching (fallback method)
     */
    protected function extractWithPatternMatching(string $imageContent): ?array
    {
        // This is a placeholder for fallback OCR using Tesseract or other methods
        // You can implement Tesseract OCR here
        Log::info('Using pattern matching OCR (fallback method)');
        return null;
    }

    /**
     * Parse extracted text to identify Thai ID card fields
     */
    protected function parseThaiIdCardText(string $text): array
    {
        $data = [
            'id_card_number' => null,
            'thai_first_name' => null,
            'thai_last_name' => null,
            'english_first_name' => null,
            'english_last_name' => null,
            'birth_date' => null,
            'religion' => null,
            'address' => null,
            'issue_date' => null,
            'expiry_date' => null,
        ];

        // Extract ID card number (13 digits)
        if (preg_match('/(\d\s*\d{4}\s*\d{5}\s*\d{2}\s*\d|\d{13})/', $text, $matches)) {
            $data['id_card_number'] = preg_replace('/\s+/', '', $matches[1]);
        }

        // Extract dates (DD MMM YYYY format in Thai or DD/MM/YYYY)
        $dates = [];
        preg_match_all('/(\d{1,2}\s*(?:ม\.ค|ก\.พ|มี\.ค|เม\.ย|พ\.ค|มิ\.ย|ก\.ค|ส\.ค|ก\.ย|ต\.ค|พ\.ย|ธ\.ค|มกราคม|กุมภาพันธ์|มีนาคม|เมษายน|พฤษภาคม|มิถุนายน|กรกฎาคม|สิงหาคม|กันยายน|ตุลาคม|พฤศจิกายน|ธันวาคม)\.*\s*\d{4})/', $text, $dateMatches);
        if (!empty($dateMatches[0])) {
            $dates = $dateMatches[0];
        }

        // Also try DD/MM/YYYY format
        preg_match_all('/(\d{1,2}\/\d{1,2}\/\d{4})/', $text, $slashDateMatches);
        if (!empty($slashDateMatches[0])) {
            $dates = array_merge($dates, $slashDateMatches[0]);
        }

        // Assign dates (typically: birth date, issue date, expiry date)
        if (count($dates) >= 1) {
            $data['birth_date'] = $this->parseThaiDate($dates[0]);
        }
        if (count($dates) >= 2) {
            $data['issue_date'] = $this->parseThaiDate($dates[1]);
        }
        if (count($dates) >= 3) {
            $data['expiry_date'] = $this->parseThaiDate($dates[2]);
        }

        // Extract Thai name (look for patterns like "ชื่อตัว", "นาย", "นาง", "นางสาว")
        $lines = explode("\n", $text);
        foreach ($lines as $i => $line) {
            // Look for Thai name patterns
            if (preg_match('/(นาย|นาง|นางสาว|เด็กชาย|เด็กหญิง)\s*([ก-๙]+)/', $line, $matches)) {
                $data['thai_first_name'] = trim($matches[2]);

                // Next line might be last name
                if (isset($lines[$i + 1])) {
                    $nextLine = trim($lines[$i + 1]);
                    if (preg_match('/^([ก-๙]+)$/', $nextLine, $lastNameMatch)) {
                        $data['thai_last_name'] = $lastNameMatch[1];
                    }
                }
            }

            // Look for English name (usually "Name" followed by English text)
            if (preg_match('/Name\s+([A-Z][a-z]+)/', $line, $matches)) {
                $data['english_first_name'] = $matches[1];
            }
            if (preg_match('/Last\s*name\s+([A-Z][a-z]+)/i', $line, $matches)) {
                $data['english_last_name'] = $matches[1];
            }

            // Look for religion
            if (preg_match('/ศาสนา\s*([ก-๙]+)/', $line, $matches)) {
                $data['religion'] = trim($matches[1]);
            }
        }

        // Extract address (look for "ที่อยู่" or address patterns)
        if (preg_match('/ที่อยู่\s*(.+?)(?=วันเกิด|เกิด|Date|$)/s', $text, $matches)) {
            $data['address'] = trim($matches[1]);
        }

        return $data;
    }

    /**
     * Parse Thai date format to Y-m-d
     */
    protected function parseThaiDate(string $dateString): ?string
    {
        try {
            // Thai month abbreviations
            $thaiMonths = [
                'ม.ค' => '01', 'มกราคม' => '01',
                'ก.พ' => '02', 'กุมภาพันธ์' => '02',
                'มี.ค' => '03', 'มีนาคม' => '03',
                'เม.ย' => '04', 'เมษายน' => '04',
                'พ.ค' => '05', 'พฤษภาคม' => '05',
                'มิ.ย' => '06', 'มิถุนายน' => '06',
                'ก.ค' => '07', 'กรกฎาคม' => '07',
                'ส.ค' => '08', 'สิงหาคม' => '08',
                'ก.ย' => '09', 'กันยายน' => '09',
                'ต.ค' => '10', 'ตุลาคม' => '10',
                'พ.ย' => '11', 'พฤศจิกายน' => '11',
                'ธ.ค' => '12', 'ธันวาคม' => '12',
            ];

            // Try DD/MM/YYYY format first
            if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dateString, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];

                // Convert Buddhist year to Christian year if needed
                if ($year > 2500) {
                    $year = $year - 543;
                }

                return "$year-$month-$day";
            }

            // Parse Thai format
            foreach ($thaiMonths as $thaiMonth => $monthNum) {
                if (strpos($dateString, $thaiMonth) !== false) {
                    preg_match('/(\d{1,2})\s*' . preg_quote($thaiMonth) . '\.*\s*(\d{4})/', $dateString, $matches);
                    if (count($matches) >= 3) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $year = $matches[2];

                        // Convert Buddhist year to Christian year
                        if ($year > 2500) {
                            $year = $year - 543;
                        }

                        return "$year-$monthNum-$day";
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Date parsing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if Google credentials are configured
     */
    protected function hasGoogleCredentials(): bool
    {
        $credentialsPath = config('services.google.credentials_path');
        return !empty($credentialsPath) && file_exists($credentialsPath);
    }

    /**
     * Validate Thai ID card number (checksum)
     */
    public function validateIdCardNumber(string $idNumber): bool
    {
        // Remove spaces and check length
        $idNumber = preg_replace('/\s+/', '', $idNumber);

        if (strlen($idNumber) !== 13 || !ctype_digit($idNumber)) {
            return false;
        }

        // Calculate checksum
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$idNumber[$i] * (13 - $i);
        }

        $checkDigit = (11 - ($sum % 11)) % 10;

        return $checkDigit == (int)$idNumber[12];
    }

    /**
     * Close the Google Vision client
     */
    public function __destruct()
    {
        if ($this->client) {
            $this->client->close();
        }
    }
}
