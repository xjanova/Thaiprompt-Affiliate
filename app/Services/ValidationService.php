<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Validation Service
 *
 * Provides comprehensive validation for Thai-specific formats:
 * - Thai phone numbers (mobile and landline)
 * - Thai ID card numbers (with checksum validation)
 * - Email addresses (RFC compliant)
 * - Thai names and addresses
 */
class ValidationService
{
    /**
     * Validate Thai mobile phone number
     *
     * Formats supported:
     * - 06x-09x (Thai mobile prefixes)
     * - 0812345678 (10 digits starting with 06-09)
     * - +66812345678 (international format)
     * - 66812345678 (without +)
     *
     * @param string $phone
     * @return array ['valid' => bool, 'formatted' => string|null, 'errors' => array]
     */
    public function validateThaiPhone(string $phone): array
    {
        // Remove spaces, dashes, and parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Pattern 1: 0XXXXXXXX (10 digits, starts with 06-09)
        if (preg_match('/^0([6-9]\d{8})$/', $cleaned, $matches)) {
            return [
                'valid' => true,
                'formatted' => '0' . $matches[1],
                'type' => 'mobile',
                'errors' => [],
            ];
        }

        // Pattern 2: +66XXXXXXXX or 66XXXXXXXX
        if (preg_match('/^\+?66([6-9]\d{8})$/', $cleaned, $matches)) {
            return [
                'valid' => true,
                'formatted' => '0' . $matches[1],
                'type' => 'mobile',
                'errors' => [],
            ];
        }

        // Pattern 3: Thai landline (02-XXXXXXX for Bangkok)
        if (preg_match('/^0([2-5]\d{7,8})$/', $cleaned, $matches)) {
            return [
                'valid' => true,
                'formatted' => '0' . $matches[1],
                'type' => 'landline',
                'errors' => [],
            ];
        }

        return [
            'valid' => false,
            'formatted' => null,
            'type' => null,
            'errors' => ['เบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกเบอร์มือถือที่ขึ้นต้นด้วย 06, 07, 08 หรือ 09'],
        ];
    }

    /**
     * Validate Thai national ID card number
     *
     * Uses checksum algorithm to verify Thai ID card validity
     *
     * @param string $idCard
     * @return array
     */
    public function validateThaiIdCard(string $idCard): array
    {
        // Remove spaces and dashes
        $cleaned = preg_replace('/[\s\-]/', '', $idCard);

        // Must be exactly 13 digits
        if (!preg_match('/^\d{13}$/', $cleaned)) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก'],
            ];
        }

        // Validate checksum using Thai ID card algorithm
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cleaned[$i] * (13 - $i);
        }

        $checksum = (11 - ($sum % 11)) % 10;
        $lastDigit = (int)$cleaned[12];

        if ($checksum !== $lastDigit) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'],
            ];
        }

        // Format: X-XXXX-XXXXX-XX-X
        $formatted = sprintf(
            '%s-%s-%s-%s-%s',
            substr($cleaned, 0, 1),
            substr($cleaned, 1, 4),
            substr($cleaned, 5, 5),
            substr($cleaned, 10, 2),
            substr($cleaned, 12, 1)
        );

        return [
            'valid' => true,
            'formatted' => $formatted,
            'raw' => $cleaned,
            'errors' => [],
        ];
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @param bool $checkMxRecord Check if MX record exists
     * @return array
     */
    public function validateEmail(string $email, bool $checkMxRecord = false): array
    {
        $email = trim(strtolower($email));

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['รูปแบบอีเมลไม่ถูกต้อง'],
            ];
        }

        // Check for common typos
        $commonTypos = [
            'gamil.com' => 'gmail.com',
            'gmai.com' => 'gmail.com',
            'gmial.com' => 'gmail.com',
            'hotmial.com' => 'hotmail.com',
            'yaho.com' => 'yahoo.com',
            'yahooo.com' => 'yahoo.com',
        ];

        list($localPart, $domain) = explode('@', $email);

        $suggestion = null;
        if (isset($commonTypos[$domain])) {
            $suggestion = $localPart . '@' . $commonTypos[$domain];
        }

        // Check MX record if requested
        $mxRecordExists = true;
        if ($checkMxRecord) {
            $mxRecordExists = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        }

        if (!$mxRecordExists) {
            return [
                'valid' => false,
                'formatted' => $email,
                'suggestion' => $suggestion,
                'errors' => ['โดเมนอีเมลนี้ไม่มีอยู่จริง กรุณาตรวจสอบอีกครั้ง'],
            ];
        }

        return [
            'valid' => true,
            'formatted' => $email,
            'suggestion' => $suggestion,
            'domain' => $domain,
            'errors' => [],
        ];
    }

    /**
     * Validate Thai name (supports Thai and English)
     *
     * @param string $name
     * @param string $type 'thai' or 'english' or 'both'
     * @return array
     */
    public function validateThaiName(string $name, string $type = 'both'): array
    {
        $name = trim($name);

        // Must not be empty
        if (empty($name)) {
            return [
                'valid' => false,
                'errors' => ['กรุณากรอกชื่อ'],
            ];
        }

        // Must be at least 2 characters
        if (mb_strlen($name) < 2) {
            return [
                'valid' => false,
                'errors' => ['ชื่อต้องมีอย่างน้อย 2 ตัวอักษร'],
            ];
        }

        // Check for Thai characters
        $hasThai = preg_match('/[\x{0E00}-\x{0E7F}]/u', $name);
        // Check for English characters
        $hasEnglish = preg_match('/[a-zA-Z]/', $name);

        if ($type === 'thai' && !$hasThai) {
            return [
                'valid' => false,
                'errors' => ['กรุณากรอกชื่อเป็นภาษาไทย'],
            ];
        }

        if ($type === 'english' && !$hasEnglish) {
            return [
                'valid' => false,
                'errors' => ['กรุณากรอกชื่อเป็นภาษาอังกฤษ'],
            ];
        }

        // Check for invalid characters (numbers, special chars except spaces and Thai tone marks)
        if (preg_match('/[0-9!@#$%^&*()_+=\[\]{};:"\\|,.<>\/?]/', $name)) {
            return [
                'valid' => false,
                'errors' => ['ชื่อไม่สามารถมีตัวเลขหรืออักขระพิเศษได้'],
            ];
        }

        return [
            'valid' => true,
            'formatted' => ucwords($name),
            'has_thai' => $hasThai,
            'has_english' => $hasEnglish,
            'errors' => [],
        ];
    }

    /**
     * Validate Thai address
     *
     * @param string $address
     * @return array
     */
    public function validateThaiAddress(string $address): array
    {
        $address = trim($address);

        if (empty($address)) {
            return [
                'valid' => false,
                'errors' => ['กรุณากรอกที่อยู่'],
            ];
        }

        // Must be at least 10 characters for a meaningful address
        if (mb_strlen($address) < 10) {
            return [
                'valid' => false,
                'errors' => ['ที่อยู่ต้องมีความยาวอย่างน้อย 10 ตัวอักษร'],
            ];
        }

        return [
            'valid' => true,
            'formatted' => $address,
            'errors' => [],
        ];
    }

    /**
     * Validate Thai postal code
     *
     * @param string $postalCode
     * @return array
     */
    public function validateThaiPostalCode(string $postalCode): array
    {
        $cleaned = preg_replace('/\s/', '', $postalCode);

        if (!preg_match('/^\d{5}$/', $cleaned)) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก'],
            ];
        }

        return [
            'valid' => true,
            'formatted' => $cleaned,
            'errors' => [],
        ];
    }

    /**
     * Validate date of birth
     *
     * @param string $date Format: Y-m-d or d/m/Y
     * @param int $minAge Minimum age required
     * @param int $maxAge Maximum age allowed
     * @return array
     */
    public function validateDateOfBirth(string $date, int $minAge = 13, int $maxAge = 120): array
    {
        try {
            // Try different date formats
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
            $dateObj = null;

            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj !== false) {
                    break;
                }
            }

            if (!$dateObj) {
                return [
                    'valid' => false,
                    'errors' => ['รูปแบบวันที่ไม่ถูกต้อง กรุณาใช้รูปแบบ วัน/เดือน/ปี'],
                ];
            }

            // Calculate age
            $today = new \DateTime();
            $age = $today->diff($dateObj)->y;

            // Check if date is in the future
            if ($dateObj > $today) {
                return [
                    'valid' => false,
                    'errors' => ['วันเกิดไม่สามารถเป็นวันในอนาคตได้'],
                ];
            }

            // Check minimum age
            if ($age < $minAge) {
                return [
                    'valid' => false,
                    'errors' => ["อายุต้องมากกว่า {$minAge} ปี"],
                ];
            }

            // Check maximum age
            if ($age > $maxAge) {
                return [
                    'valid' => false,
                    'errors' => ["อายุต้องไม่เกิน {$maxAge} ปี"],
                ];
            }

            return [
                'valid' => true,
                'formatted' => $dateObj->format('Y-m-d'),
                'age' => $age,
                'errors' => [],
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['รูปแบบวันที่ไม่ถูกต้อง'],
            ];
        }
    }

    /**
     * Comprehensive validation for multiple fields
     *
     * @param array $data
     * @param array $rules Format: ['field' => 'rule1|rule2']
     * @return array
     */
    public function validateMultiple(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "กรุณากรอก {$field}";
                    continue 2; // Skip to next field
                }

                if ($rule === 'thai_phone') {
                    $result = $this->validateThaiPhone($value);
                    if (!$result['valid']) {
                        $errors[$field] = $result['errors'];
                        continue 2;
                    }
                    $validated[$field] = $result['formatted'];
                }

                if ($rule === 'thai_id') {
                    $result = $this->validateThaiIdCard($value);
                    if (!$result['valid']) {
                        $errors[$field] = $result['errors'];
                        continue 2;
                    }
                    $validated[$field] = $result['formatted'];
                }

                if ($rule === 'email') {
                    $result = $this->validateEmail($value);
                    if (!$result['valid']) {
                        $errors[$field] = $result['errors'];
                        continue 2;
                    }
                    $validated[$field] = $result['formatted'];
                }

                if ($rule === 'thai_name') {
                    $result = $this->validateThaiName($value);
                    if (!$result['valid']) {
                        $errors[$field] = $result['errors'];
                        continue 2;
                    }
                    $validated[$field] = $result['formatted'];
                }
            }

            // If no errors, use original value if not already validated
            if (!isset($errors[$field]) && !isset($validated[$field])) {
                $validated[$field] = $value;
            }
        }

        return [
            'valid' => empty($errors),
            'validated' => $validated,
            'errors' => $errors,
        ];
    }
}
