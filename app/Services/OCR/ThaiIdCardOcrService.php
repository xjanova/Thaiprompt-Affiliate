<?php

namespace App\Services\OCR;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;

/**
 * Thai ID Card OCR Service
 *
 * รองรับ 2 วิธีในการเชื่อมต่อ Google Cloud Vision:
 * 1. Service Account Credentials (JSON file) - สำหรับ SDK
 * 2. API Key (REST API) - สำหรับใช้งานร่วมกับ Google Maps API Key
 *
 * @version 2.0.0
 */
class ThaiIdCardOcrService
{
    protected $client;
    protected ?string $apiKey = null;
    protected bool $useRestApi = false;

    /**
     * Vision API REST endpoint
     */
    protected const VISION_API_URL = 'https://vision.googleapis.com/v1/images:annotate';

    public function __construct()
    {
        // ลำดับการตรวจสอบ credentials:
        // 1. ตรวจสอบ API Key จาก settings (สำหรับ REST API)
        // 2. ตรวจสอบ Service Account credentials (สำหรับ SDK)

        // ลองใช้ API Key ก่อน (ถ้าตั้งค่า cloud_vision_api_key)
        $this->apiKey = Setting::get('cloud_vision_api_key')
                        ?: Setting::get('google_maps_api_key') // Fallback ใช้ Maps API Key
                        ?: config('services.google_maps.api_key')
                        ?: null;

        // ถ้ามี API Key ให้ใช้ REST API
        if ($this->apiKey) {
            $this->useRestApi = true;
            Log::info('ThaiIdCardOcrService: ใช้ REST API + API Key');
        }
        // ถ้าไม่มี API Key ลองใช้ Service Account credentials
        elseif ($this->hasGoogleCredentials()) {
            try {
                $this->client = new ImageAnnotatorClient([
                    'credentials' => config('services.google.credentials_path')
                ]);
                Log::info('ThaiIdCardOcrService: ใช้ SDK + Service Account');
            } catch (\Exception $e) {
                Log::warning('Failed to initialize Google Vision API: ' . $e->getMessage());
                $this->client = null;
            }
        }
    }

    /**
     * ตรวจสอบสถานะการเชื่อมต่อ API
     *
     * @return array ข้อมูลสถานะและ capabilities
     */
    public function getApiStatus(): array
    {
        $status = [
            'configured' => false,
            'method' => null,
            'api_key_masked' => null,
            'credentials_file' => null,
            'capabilities' => [],
            'error' => null,
        ];

        if ($this->useRestApi && $this->apiKey) {
            $status['configured'] = true;
            $status['method'] = 'REST API + API Key';
            $status['api_key_masked'] = substr($this->apiKey, 0, 8) . '...' . substr($this->apiKey, -4);
            $status['capabilities'] = [
                'text_detection' => true,
                'document_text_detection' => true,
                'label_detection' => true,
                'face_detection' => true,
            ];

            // ทดสอบการเชื่อมต่อ
            try {
                $testResult = $this->testApiConnection();
                $status['connection_test'] = $testResult;
            } catch (\Exception $e) {
                $status['error'] = $e->getMessage();
            }
        } elseif ($this->client) {
            $status['configured'] = true;
            $status['method'] = 'SDK + Service Account';
            $status['credentials_file'] = config('services.google.credentials_path');
            $status['capabilities'] = [
                'text_detection' => true,
                'document_text_detection' => true,
                'label_detection' => true,
                'face_detection' => true,
                'object_localization' => true,
                'product_search' => true,
            ];
        } else {
            $status['error'] = 'ไม่พบการตั้งค่า API Key หรือ Service Account credentials';
        }

        return $status;
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     */
    protected function testApiConnection(): array
    {
        if (!$this->useRestApi || !$this->apiKey) {
            return ['success' => false, 'error' => 'ไม่ได้ใช้ REST API'];
        }

        try {
            // สร้างรูปขนาดเล็กสำหรับทดสอบ (1x1 pixel transparent PNG)
            $testImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

            $response = Http::timeout(10)->post(self::VISION_API_URL . '?key=' . $this->apiKey, [
                'requests' => [
                    [
                        'image' => ['content' => $testImage],
                        'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]],
                    ]
                ]
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'เชื่อมต่อสำเร็จ'];
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract data from Thai ID card or driver's license image
     *
     * @param string $imagePath Path to the image file
     * @return array Extracted data with error information
     */
    public function extractData(string $imagePath): array
    {
        Log::info('OCR extractData started', [
            'imagePath' => $imagePath,
            'useRestApi' => $this->useRestApi,
            'hasApiKey' => !empty($this->apiKey),
            'hasClient' => !is_null($this->client),
        ]);

        try {
            // Check if image file exists
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::warning('OCR: Image file not found', ['path' => $imagePath]);
                return [
                    'success' => false,
                    'error' => 'ไม่พบไฟล์รูปภาพ',
                    'error_code' => 'FILE_NOT_FOUND',
                    'suggestion' => 'กรุณาอัพโหลดรูปภาพใหม่อีกครั้ง'
                ];
            }

            // Read the image file
            $imageContent = Storage::disk('public')->get($imagePath);
            Log::info('OCR: Image loaded', ['size' => strlen($imageContent)]);

            // Validate image content
            $validation = $this->validateImageQuality($imageContent);
            if (!$validation['valid']) {
                Log::warning('OCR: Image validation failed', $validation);
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'error_code' => $validation['error_code'],
                    'suggestion' => $validation['suggestion']
                ];
            }

            Log::info('OCR: Image validation passed');

            // Preprocess image for better OCR results
            $processedContent = $this->preprocessImage($imageContent);
            Log::info('OCR: Image preprocessed', ['processed_size' => strlen($processedContent)]);

            // เลือกวิธีการ OCR
            $result = null;

            // วิธี 1: ใช้ REST API + API Key
            if ($this->useRestApi && $this->apiKey) {
                Log::info('OCR: Using REST API method');
                $result = $this->extractWithRestApi($processedContent);
            }
            // วิธี 2: ใช้ SDK + Service Account
            elseif ($this->client) {
                Log::info('OCR: Using SDK method');
                $result = $this->extractWithGoogleVision($processedContent);
            }

            if ($result === null) {
                // ตรวจสอบว่ามี API ใดเลยหรือไม่
                if (!$this->useRestApi && !$this->client) {
                    Log::error('OCR: Not configured - no API key or credentials');
                    return [
                        'success' => false,
                        'error' => 'ระบบ OCR ยังไม่พร้อมใช้งาน',
                        'error_code' => 'OCR_NOT_CONFIGURED',
                        'suggestion' => 'กรุณาตั้งค่า Google Cloud Vision API Key ในหน้าตั้งค่าระบบ หรือติดตั้ง Service Account credentials'
                    ];
                }

                Log::warning('OCR: No text detected from image');
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถตรวจจับข้อความจากรูปภาพได้',
                    'error_code' => 'NO_TEXT_DETECTED',
                    'suggestion' => 'กรุณาถ่ายรูปบัตรให้ชัดเจน มีแสงสว่างเพียงพอ และข้อความบนบัตรสามารถอ่านได้ชัดเจน'
                ];
            }

            Log::info('OCR: Text extraction completed', ['result_keys' => array_keys($result)]);

            // Check if we got meaningful data
            if ($this->hasMinimumData($result)) {
                $result['success'] = true;
                $result['ocr_method'] = $this->useRestApi ? 'REST_API' : 'SDK';
                Log::info('OCR: Success - extracted meaningful data', [
                    'id_card_number' => !empty($result['id_card_number']) ? '***' . substr($result['id_card_number'] ?? '', -4) : null,
                    'thai_first_name' => $result['thai_first_name'] ?? null,
                    'english_first_name' => $result['english_first_name'] ?? null,
                ]);
                return $result;
            } else {
                Log::warning('OCR: Insufficient data extracted', ['partial_data' => $result]);
                return [
                    'success' => false,
                    'error' => 'ตรวจพบข้อความบางส่วน แต่ไม่สามารถระบุข้อมูลสำคัญได้',
                    'error_code' => 'INSUFFICIENT_DATA',
                    'suggestion' => 'กรุณาตรวจสอบว่ารูปบัตรมีความชัดเจน ไม่เบลอ และถ่ายบัตรทั้งใบให้เห็นชัดเจน',
                    'partial_data' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error('Thai ID Card OCR Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'imagePath' => $imagePath,
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการประมวลผลรูปภาพ',
                'error_code' => 'PROCESSING_ERROR',
                'suggestion' => 'กรุณาลองอัพโหลดรูปภาพใหม่อีกครั้ง หรือใช้รูปภาพอื่น',
                'technical_error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract text using REST API + API Key
     *
     * @param string $imageContent Base64 encoded image content
     * @return array|null Extracted data or null if failed
     */
    protected function extractWithRestApi(string $imageContent): ?array
    {
        try {
            $base64Image = base64_encode($imageContent);

            $response = Http::timeout(30)->post(self::VISION_API_URL . '?key=' . $this->apiKey, [
                'requests' => [
                    [
                        'image' => ['content' => $base64Image],
                        'features' => [
                            ['type' => 'TEXT_DETECTION'],
                            ['type' => 'DOCUMENT_TEXT_DETECTION'],
                        ],
                        'imageContext' => [
                            'languageHints' => ['th', 'en'],
                        ],
                    ]
                ]
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Unknown API error';
                Log::error('Vision API REST Error: ' . $error);
                return null;
            }

            $data = $response->json();

            // ตรวจสอบผลลัพธ์
            $textAnnotations = $data['responses'][0]['textAnnotations'] ?? [];

            if (empty($textAnnotations)) {
                return null;
            }

            // ดึงข้อความทั้งหมด
            $fullText = $textAnnotations[0]['description'] ?? '';

            if (empty($fullText)) {
                return null;
            }

            // Detect document type
            $documentType = $this->detectDocumentType($fullText);

            Log::info('OCR (REST API) detected document type: ' . $documentType);

            // Parse based on document type
            if ($documentType === 'driver_license') {
                return $this->parseThaiDriverLicense($fullText);
            } else {
                return $this->parseThaiIdCardText($fullText);
            }
        } catch (\Exception $e) {
            Log::error('Vision API REST Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate image quality before OCR
     */
    protected function validateImageQuality(string $imageContent): array
    {
        // Check file size (too small = likely corrupted or screenshot)
        $size = strlen($imageContent);
        if ($size < 10000) { // Less than 10KB
            return [
                'valid' => false,
                'error' => 'ไฟล์รูปภาพมีขนาดเล็กเกินไป',
                'error_code' => 'FILE_TOO_SMALL',
                'suggestion' => 'กรุณาใช้รูปถ่ายจากกล้องที่มีความละเอียดสูง ไม่ใช่ภาพหน้าจอ (screenshot)'
            ];
        }

        // Check if it's a valid image
        try {
            $imageInfo = @getimagesizefromstring($imageContent);
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'error' => 'ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง',
                    'error_code' => 'INVALID_IMAGE',
                    'suggestion' => 'กรุณาอัพโหลดไฟล์รูปภาพ (JPEG, PNG) เท่านั้น'
                ];
            }

            // Check image dimensions (too small = poor quality)
            [$width, $height] = $imageInfo;
            if ($width < 300 || $height < 200) {
                return [
                    'valid' => false,
                    'error' => 'ความละเอียดของรูปภาพต่ำเกินไป (' . $width . 'x' . $height . ')',
                    'error_code' => 'LOW_RESOLUTION',
                    'suggestion' => 'กรุณาถ่ายรูปใหม่ด้วยกล้องที่มีความละเอียดสูงกว่า หรือเข้าใกล้บัตรมากขึ้น (แนะนำ: อย่างน้อย 800x600 pixels)'
                ];
            }

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'ไม่สามารถตรวจสอบคุณภาพรูปภาพได้',
                'error_code' => 'VALIDATION_ERROR',
                'suggestion' => 'กรุณาลองอัพโหลดรูปภาพใหม่'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Preprocess image for better OCR results
     */
    protected function preprocessImage(string $imageContent): string
    {
        try {
            // Use Intervention Image for preprocessing
            $imageManager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $imageManager->read($imageContent);

            // Auto adjust brightness and contrast
            $image->brightness(5);
            $image->contrast(10);

            // Sharpen the image for better text recognition
            $image->sharpen(10);

            // Convert to high quality JPEG for Google Vision
            $encoded = $image->toJpeg(95);

            return (string) $encoded;
        } catch (\Exception $e) {
            Log::warning('Image preprocessing failed, using original: ' . $e->getMessage());
            return $imageContent;
        }
    }

    /**
     * Check if extracted data has minimum required information
     *
     * V2.0: ผ่อนปรนเงื่อนไขให้ผ่านง่ายขึ้น - มีข้อมูลอย่างน้อย 1 อย่างก็ถือว่าสำเร็จ
     */
    protected function hasMinimumData(array $data): bool
    {
        // นับจำนวนข้อมูลที่ได้
        $fieldsFound = 0;

        // For ID card
        if ($data['document_type'] === 'id_card') {
            if (!empty($data['id_card_number'])) $fieldsFound++;
            if (!empty($data['thai_first_name'])) $fieldsFound++;
            if (!empty($data['thai_last_name'])) $fieldsFound++;
            if (!empty($data['english_first_name'])) $fieldsFound++;
            if (!empty($data['english_last_name'])) $fieldsFound++;
            if (!empty($data['birth_date'])) $fieldsFound++;
            if (!empty($data['address'])) $fieldsFound++;

            Log::info('OCR hasMinimumData check:', [
                'document_type' => 'id_card',
                'fields_found' => $fieldsFound,
                'has_id' => !empty($data['id_card_number']),
                'has_thai_name' => !empty($data['thai_first_name']),
                'has_english_name' => !empty($data['english_first_name']),
            ]);

            // ผ่านถ้ามีเลขบัตร หรือ มีชื่อ (ไทย/อังกฤษ) หรือ มีข้อมูลอย่างน้อย 2 อย่าง
            return !empty($data['id_card_number']) ||
                   !empty($data['thai_first_name']) ||
                   !empty($data['english_first_name']) ||
                   $fieldsFound >= 2;
        }

        // For driver license
        if ($data['document_type'] === 'driver_license') {
            if (!empty($data['license_number'])) $fieldsFound++;
            if (!empty($data['id_card_number'])) $fieldsFound++;
            if (!empty($data['thai_first_name'])) $fieldsFound++;
            if (!empty($data['english_first_name'])) $fieldsFound++;

            return !empty($data['license_number']) ||
                   !empty($data['id_card_number']) ||
                   !empty($data['thai_first_name']) ||
                   !empty($data['english_first_name']) ||
                   $fieldsFound >= 2;
        }

        return false;
    }

    /**
     * Extract data using Google Cloud Vision API (SDK)
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

            // Detect document type
            $documentType = $this->detectDocumentType($fullText);

            Log::info('OCR (SDK) detected document type: ' . $documentType);

            // Parse based on document type
            if ($documentType === 'driver_license') {
                return $this->parseThaiDriverLicense($fullText);
            } else {
                return $this->parseThaiIdCardText($fullText);
            }
        } catch (\Exception $e) {
            Log::error('Google Vision API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect if the document is an ID card or driver's license
     */
    protected function detectDocumentType(string $text): string
    {
        // Look for driver's license specific keywords
        $driverLicenseKeywords = [
            'ใบอนุญาตขับ',
            'ใบขับขี่',
            'DRIVING LICENCE',
            'DRIVER LICENCE',
            'LICENSE TO DRIVE',
            'ใบขับรถ',
            'ประเภทรถ',
            'เลขที่ใบอนุญาต',
        ];

        // Look for ID card specific keywords
        $idCardKeywords = [
            'บัตรประจำตัวประชาชน',
            'บัตรประชาชน',
            'THAI NATIONAL ID CARD',
            'IDENTIFICATION NUMBER',
            'เลขประจำตัวประชาชน',
        ];

        $text = mb_strtolower($text, 'UTF-8');

        // Count matches for each type
        $driverLicenseMatches = 0;
        foreach ($driverLicenseKeywords as $keyword) {
            if (mb_stripos($text, mb_strtolower($keyword, 'UTF-8')) !== false) {
                $driverLicenseMatches++;
            }
        }

        $idCardMatches = 0;
        foreach ($idCardKeywords as $keyword) {
            if (mb_stripos($text, mb_strtolower($keyword, 'UTF-8')) !== false) {
                $idCardMatches++;
            }
        }

        // Determine document type based on matches
        if ($driverLicenseMatches > $idCardMatches) {
            return 'driver_license';
        }

        return 'id_card'; // Default to ID card
    }

    /**
     * Parse Thai driver's license text
     */
    protected function parseThaiDriverLicense(string $text): array
    {
        $data = [
            'document_type' => 'driver_license',
            'license_number' => null,
            'id_card_number' => null,
            'thai_first_name' => null,
            'thai_last_name' => null,
            'english_first_name' => null,
            'english_last_name' => null,
            'birth_date' => null,
            'issue_date' => null,
            'expiry_date' => null,
            'address' => null,
            'license_type' => null, // ประเภทรถ
        ];

        // Extract license number (8-11 digits)
        if (preg_match('/(?:เลขที่|License\s*No\.?|ใบอนุญาตเลขที่)\s*[:.]?\s*(\d{8,11})/', $text, $matches)) {
            $data['license_number'] = $matches[1];
        }

        // Extract ID card number from driver's license (13 digits)
        if (preg_match('/(?:เลขประจำตัว|ID\s*Number)\s*[:.]?\s*(\d[\s-]*\d{4}[\s-]*\d{5}[\s-]*\d{2}[\s-]*\d)/', $text, $matches)) {
            $data['id_card_number'] = preg_replace('/[\s-]+/', '', $matches[1]);
        } elseif (preg_match('/(\d\s*\d{4}\s*\d{5}\s*\d{2}\s*\d|\d{13})/', $text, $matches)) {
            $idNumber = preg_replace('/\s+/', '', $matches[1]);
            if (strlen($idNumber) === 13) {
                $data['id_card_number'] = $idNumber;
            }
        }

        // Extract dates
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

        // Assign dates (birth date, issue date, expiry date)
        if (count($dates) >= 1) {
            $data['birth_date'] = $this->parseThaiDate($dates[0]);
        }
        if (count($dates) >= 2) {
            $data['issue_date'] = $this->parseThaiDate($dates[1]);
        }
        if (count($dates) >= 3) {
            $data['expiry_date'] = $this->parseThaiDate($dates[2]);
        }

        // Extract names
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

            // Look for English name
            if (preg_match('/(?:Name|ชื่อ)\s+(?:Mr\.|Mrs\.|Miss\.)?\s*([A-Z][a-z]+)/', $line, $matches)) {
                $data['english_first_name'] = $matches[1];
            }
            if (preg_match('/(?:Last\s*name|Surname|นามสกุล)\s+([A-Z][a-z]+)/i', $line, $matches)) {
                $data['english_last_name'] = $matches[1];
            }

            // Extract license type (ประเภทรถ)
            if (preg_match('/ประเภทรถ\s*[:.]?\s*(.+?)(?=\n|$)/', $line, $matches)) {
                $data['license_type'] = trim($matches[1]);
            }
        }

        // Extract address
        if (preg_match('/ที่อยู่\s*[:.]?\s*(.+?)(?=วันเกิด|เกิด|Date|ออกบัตร|$)/s', $text, $matches)) {
            $data['address'] = trim($matches[1]);
        }

        return $data;
    }

    /**
     * Parse extracted text to identify Thai ID card fields
     *
     * ปรับปรุง V2.0: เพิ่ม patterns เพื่อรองรับบัตรประชาชนหลายรูปแบบ
     */
    protected function parseThaiIdCardText(string $text): array
    {
        $data = [
            'document_type' => 'id_card',
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

        // Log raw text for debugging (เฉพาะ debug mode)
        Log::debug('OCR Raw Text:', ['text' => $text]);

        // ===============================================
        // 1. Extract ID card number (13 digits) - หลายรูปแบบ
        // ===============================================
        // รูปแบบ: X-XXXX-XXXXX-XX-X, X XXXX XXXXX XX X, หรือติดกัน
        $idPatterns = [
            // รูปแบบมีขีด: 1-2345-67890-12-3
            '/(\d[-\s]*\d{4}[-\s]*\d{5}[-\s]*\d{2}[-\s]*\d)/',
            // รูปแบบมี space: 1 2345 67890 12 3
            '/(\d\s+\d{4}\s+\d{5}\s+\d{2}\s+\d)/',
            // รูปแบบติดกัน 13 หลัก
            '/(\d{13})/',
            // รูปแบบใกล้กับ "เลขประจำตัวประชาชน" หรือ "Identification"
            '/(?:เลข(?:ประจำตัว)?(?:ประชาชน)?|Identification\s*(?:Number)?)\s*[:\s]*(\d[\d\s-]{11,16}\d)/',
        ];

        foreach ($idPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cleanId = preg_replace('/[\s-]+/', '', $matches[1]);
                if (strlen($cleanId) === 13 && ctype_digit($cleanId)) {
                    $data['id_card_number'] = $cleanId;
                    Log::debug('OCR Found ID:', ['id' => $cleanId, 'pattern' => $pattern]);
                    break;
                }
            }
        }

        // ===============================================
        // 2. Extract Thai Name - หลายรูปแบบ
        // ===============================================
        // รูปแบบ: นาย/นาง/นางสาว ชื่อ นามสกุล หรือ ชื่อตัว ชื่อ / ชื่อสกุล นามสกุล
        $thaiNamePatterns = [
            // รูปแบบ: นาย ชื่อ นามสกุล (ในบรรทัดเดียว)
            '/(นาย|นาง|นางสาว|น\.ส\.|เด็กชาย|เด็กหญิง|ด\.ช\.|ด\.ญ\.)\s*([ก-๙]+)\s+([ก-๙]+)/',
            // รูปแบบ: ชื่อตัว ชื่อ (แยกบรรทัด)
            '/ชื่อตัว[และ]*ชื่อสกุล\s*[:\s]*([ก-๙]+)\s*([ก-๙]+)?/',
            // รูปแบบ: ชื่อ นามสกุล (หลัง prefix)
            '/(นาย|นาง|นางสาว)\s*([ก-๙\s]+)/',
        ];

        foreach ($thaiNamePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[2]) && !empty($matches[2])) {
                    $data['thai_first_name'] = trim($matches[2]);
                }
                if (isset($matches[3]) && !empty($matches[3])) {
                    $data['thai_last_name'] = trim($matches[3]);
                }
                if (!empty($data['thai_first_name'])) {
                    Log::debug('OCR Found Thai Name:', [
                        'first' => $data['thai_first_name'],
                        'last' => $data['thai_last_name']
                    ]);
                    break;
                }
            }
        }

        // ถ้ายังไม่เจอนามสกุล ลองหาจากบรรทัดถัดไป
        if (!empty($data['thai_first_name']) && empty($data['thai_last_name'])) {
            $lines = explode("\n", $text);
            foreach ($lines as $i => $line) {
                if (preg_match('/(นาย|นาง|นางสาว|น\.ส\.)\s*' . preg_quote($data['thai_first_name']) . '/', $line)) {
                    if (isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        if (preg_match('/^([ก-๙]+)$/u', $nextLine, $lastNameMatch)) {
                            $data['thai_last_name'] = $lastNameMatch[1];
                            break;
                        }
                    }
                }
            }
        }

        // ===============================================
        // 3. Extract English Name - หลายรูปแบบ
        // ===============================================
        $englishNamePatterns = [
            // รูปแบบ: Name Mr. FIRST LAST หรือ Miss FIRST LAST
            '/Name\s+(?:Mr\.|Mrs\.|Miss|Ms\.?)\s*([A-Z][a-zA-Z]+)\s+([A-Z][a-zA-Z]+)/',
            // รูปแบบ: Mr./Mrs./Miss First Last
            '/(?:Mr\.|Mrs\.|Miss|Ms\.?)\s*([A-Z][a-zA-Z]+)\s+([A-Z][a-zA-Z]+)/',
            // รูปแบบ: First name FIRST / Last name LAST
            '/(?:First\s*name|Name)\s*[:\s]*([A-Z][a-zA-Z]+)/',
        ];

        foreach ($englishNamePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1]) && !empty($matches[1])) {
                    $data['english_first_name'] = trim($matches[1]);
                }
                if (isset($matches[2]) && !empty($matches[2])) {
                    $data['english_last_name'] = trim($matches[2]);
                }
                if (!empty($data['english_first_name'])) {
                    Log::debug('OCR Found English Name:', [
                        'first' => $data['english_first_name'],
                        'last' => $data['english_last_name']
                    ]);
                    break;
                }
            }
        }

        // ลองหา Last name แยก
        if (!empty($data['english_first_name']) && empty($data['english_last_name'])) {
            if (preg_match('/(?:Last\s*name|Surname)\s*[:\s]*([A-Z][a-zA-Z]+)/i', $text, $matches)) {
                $data['english_last_name'] = trim($matches[1]);
            }
        }

        // ===============================================
        // 4. Extract Dates - วันเกิด, วันออกบัตร, วันหมดอายุ
        // ===============================================
        $dates = [];

        // รูปแบบไทย: 1 ม.ค. 2530 หรือ 1 มกราคม 2530
        preg_match_all('/(\d{1,2})\s*(ม\.ค\.?|ก\.พ\.?|มี\.ค\.?|เม\.ย\.?|พ\.ค\.?|มิ\.ย\.?|ก\.ค\.?|ส\.ค\.?|ก\.ย\.?|ต\.ค\.?|พ\.ย\.?|ธ\.ค\.?|มกราคม|กุมภาพันธ์|มีนาคม|เมษายน|พฤษภาคม|มิถุนายน|กรกฎาคม|สิงหาคม|กันยายน|ตุลาคม|พฤศจิกายน|ธันวาคม)\s*(\d{4})/u', $text, $thaiDateMatches, PREG_SET_ORDER);

        foreach ($thaiDateMatches as $match) {
            $dates[] = $match[0];
        }

        // รูปแบบ: DD/MM/YYYY หรือ DD-MM-YYYY
        preg_match_all('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $slashDateMatches, PREG_SET_ORDER);
        foreach ($slashDateMatches as $match) {
            $dates[] = $match[0];
        }

        // รูปแบบ: DD MMM YYYY (English)
        preg_match_all('/(\d{1,2})\s*(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s*(\d{4})/i', $text, $engDateMatches, PREG_SET_ORDER);
        foreach ($engDateMatches as $match) {
            $dates[] = $match[0];
        }

        // ลบ duplicates
        $dates = array_unique($dates);
        $dates = array_values($dates);

        Log::debug('OCR Found Dates:', ['dates' => $dates]);

        // ลองหาวันเกิดที่ใกล้กับ keyword "เกิด" หรือ "Date of Birth"
        if (preg_match('/(?:เกิด|Date\s*of\s*Birth|วันเกิด|DOB)\s*[:\s]*(\d{1,2}[\/\-\s](?:\d{1,2}|ม\.ค\.?|ก\.พ\.?|มี\.ค\.?|เม\.ย\.?|พ\.ค\.?|มิ\.ย\.?|ก\.ค\.?|ส\.ค\.?|ก\.ย\.?|ต\.ค\.?|พ\.ย\.?|ธ\.ค\.?|มกราคม|กุมภาพันธ์|มีนาคม|เมษายน|พฤษภาคม|มิถุนายน|กรกฎาคม|สิงหาคม|กันยายน|ตุลาคม|พฤศจิกายน|ธันวาคม)[\/\-\s]*\d{4})/iu', $text, $birthMatch)) {
            $data['birth_date'] = $this->parseThaiDate($birthMatch[1]);
        } elseif (count($dates) >= 1) {
            $data['birth_date'] = $this->parseThaiDate($dates[0]);
        }

        // ลองหาวันออกบัตรและวันหมดอายุ
        if (preg_match('/(?:ออก|Issue)\s*[:\s]*(\d{1,2}[\/\-\s][^\d]*\d{4})/iu', $text, $issueMatch)) {
            $data['issue_date'] = $this->parseThaiDate($issueMatch[1]);
        } elseif (count($dates) >= 2) {
            $data['issue_date'] = $this->parseThaiDate($dates[1]);
        }

        if (preg_match('/(?:หมดอายุ|Expiry|Exp)\s*[:\s]*(\d{1,2}[\/\-\s][^\d]*\d{4})/iu', $text, $expiryMatch)) {
            $data['expiry_date'] = $this->parseThaiDate($expiryMatch[1]);
        } elseif (count($dates) >= 3) {
            $data['expiry_date'] = $this->parseThaiDate($dates[2]);
        }

        // ===============================================
        // 5. Extract Religion
        // ===============================================
        if (preg_match('/ศาสนา\s*[:\s]*([ก-๙]+)/u', $text, $religionMatch)) {
            $data['religion'] = trim($religionMatch[1]);
        }

        // ===============================================
        // 6. Extract Address
        // ===============================================
        // ลองหาที่อยู่
        $addressPatterns = [
            '/ที่อยู่\s*[:\s]*(.+?)(?=\n\n|วันเกิด|เกิด|Date|ออก|Issue|หมดอายุ|ศาสนา|$)/su',
            '/(?:บ้านเลขที่|เลขที่)\s*(\d+[^\n]+)/u',
        ];

        foreach ($addressPatterns as $pattern) {
            if (preg_match($pattern, $text, $addressMatch)) {
                $address = trim($addressMatch[1]);
                // ทำความสะอาด address
                $address = preg_replace('/\s+/', ' ', $address);
                if (mb_strlen($address) > 10) { // ที่อยู่ต้องยาวพอ
                    $data['address'] = $address;
                    break;
                }
            }
        }

        Log::debug('OCR Parsed Data:', ['data' => $data]);

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
