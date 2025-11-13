<?php

namespace App\Services\NFC;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Exception;

/**
 * NFC Card Encryption Service
 *
 * บริการเข้ารหัสแบบสองทางสำหรับบัตร NFC เพื่อป้องกันบัตรปลอม
 * Two-way encryption/decryption service for NFC cards to prevent fake cards
 */
class NFCCardEncryptionService
{
    /**
     * Current encryption version
     */
    const ENCRYPTION_VERSION = 1;

    /**
     * Encryption algorithm
     */
    const ALGORITHM = 'AES-256-CBC';

    /**
     * Generate a new encryption key for a card
     */
    public function generateEncryptionKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Generate card signature based on card data
     */
    public function generateCardSignature(string $cardNumber, string $userId = null, array $metadata = []): string
    {
        $data = json_encode([
            'card_number' => $cardNumber,
            'user_id' => $userId,
            'metadata' => $metadata,
            'timestamp' => now()->timestamp,
            'version' => self::ENCRYPTION_VERSION,
        ]);

        return hash_hmac('sha256', $data, config('app.key'));
    }

    /**
     * Encrypt card data for writing to NFC card
     *
     * @param array $cardData ข้อมูลที่จะเข้ารหัส
     * @param string $encryptionKey คีย์สำหรับเข้ารหัส
     * @return array ['encrypted_data' => string, 'hash' => string, 'signature' => string]
     */
    public function encryptCardData(array $cardData, string $encryptionKey): array
    {
        try {
            // เพิ่มข้อมูล metadata
            $dataToEncrypt = array_merge($cardData, [
                'version' => self::ENCRYPTION_VERSION,
                'timestamp' => now()->timestamp,
                'nonce' => Str::random(16),
            ]);

            // แปลงเป็น JSON
            $jsonData = json_encode($dataToEncrypt);

            // เข้ารหัสด้วย AES-256-CBC
            $iv = random_bytes(16);
            $encrypted = openssl_encrypt(
                $jsonData,
                self::ALGORITHM,
                base64_decode($encryptionKey),
                0,
                $iv
            );

            // รวม IV กับ encrypted data
            $encryptedWithIv = base64_encode($iv . $encrypted);

            // สร้าง hash สำหรับตรวจสอบความถูกต้อง
            $hash = hash_hmac('sha256', $encryptedWithIv, $encryptionKey);

            // สร้าง signature
            $signature = $this->generateSignature($encryptedWithIv, $hash, $encryptionKey);

            return [
                'encrypted_data' => $encryptedWithIv,
                'hash' => $hash,
                'signature' => $signature,
                'version' => self::ENCRYPTION_VERSION,
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to encrypt card data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt card data read from NFC card
     *
     * @param string $encryptedData ข้อมูลที่เข้ารหัส
     * @param string $encryptionKey คีย์สำหรับถอดรหัส
     * @param string $expectedHash Hash ที่คาดหวัง
     * @return array|null ข้อมูลที่ถอดรหัสแล้ว หรือ null ถ้าถอดรหัสไม่สำเร็จ
     */
    public function decryptCardData(string $encryptedData, string $encryptionKey, string $expectedHash): ?array
    {
        try {
            // ตรวจสอบ hash
            $calculatedHash = hash_hmac('sha256', $encryptedData, $encryptionKey);

            if (!hash_equals($expectedHash, $calculatedHash)) {
                throw new Exception('Hash verification failed - possible tampering detected');
            }

            // ถอดรหัส base64
            $encryptedWithIv = base64_decode($encryptedData);

            // แยก IV และ encrypted data
            $iv = substr($encryptedWithIv, 0, 16);
            $encrypted = substr($encryptedWithIv, 16);

            // ถอดรหัสด้วย AES-256-CBC
            $decrypted = openssl_decrypt(
                $encrypted,
                self::ALGORITHM,
                base64_decode($encryptionKey),
                0,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }

            // แปลง JSON กลับเป็น array
            $data = json_decode($decrypted, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }

            // ตรวจสอบ version
            if (!isset($data['version']) || $data['version'] !== self::ENCRYPTION_VERSION) {
                throw new Exception('Unsupported encryption version');
            }

            // ตรวจสอบ timestamp (ไม่ควรเก่าเกินไป)
            if (isset($data['timestamp'])) {
                $age = now()->timestamp - $data['timestamp'];
                if ($age > 86400 * 365) { // 1 ปี
                    throw new Exception('Card data is too old');
                }
            }

            return $data;
        } catch (Exception $e) {
            throw new Exception('Failed to decrypt card data: ' . $e->getMessage());
        }
    }

    /**
     * Verify card authenticity using two-way encryption
     *
     * @param string $encryptedData ข้อมูลที่อ่านจากบัตร
     * @param string $encryptionKey คีย์สำหรับถอดรหัส
     * @param string $expectedHash Hash ที่คาดหวัง
     * @param string $expectedSignature Signature ที่คาดหวัง
     * @return array ['valid' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function verifyCardAuthenticity(
        string $encryptedData,
        string $encryptionKey,
        string $expectedHash,
        string $expectedSignature
    ): array {
        try {
            // ตรวจสอบ signature ก่อน
            $calculatedSignature = $this->generateSignature($encryptedData, $expectedHash, $encryptionKey);

            if (!hash_equals($expectedSignature, $calculatedSignature)) {
                return [
                    'valid' => false,
                    'data' => null,
                    'error' => 'Invalid card signature - fake card detected',
                ];
            }

            // ถอดรหัสข้อมูล
            $data = $this->decryptCardData($encryptedData, $encryptionKey, $expectedHash);

            if ($data === null) {
                return [
                    'valid' => false,
                    'data' => null,
                    'error' => 'Failed to decrypt card data',
                ];
            }

            return [
                'valid' => true,
                'data' => $data,
                'error' => null,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate signature for verification
     */
    protected function generateSignature(string $encryptedData, string $hash, string $encryptionKey): string
    {
        $data = $encryptedData . $hash . config('app.key');
        return hash_hmac('sha256', $data, $encryptionKey);
    }

    /**
     * Hash encryption key for storage
     */
    public function hashEncryptionKey(string $encryptionKey): string
    {
        return hash('sha256', $encryptionKey . config('app.key'));
    }

    /**
     * Verify encryption key hash
     */
    public function verifyEncryptionKeyHash(string $encryptionKey, string $hash): bool
    {
        return hash_equals($hash, $this->hashEncryptionKey($encryptionKey));
    }

    /**
     * Generate card data hash for transaction verification
     */
    public function generateCardDataHash(array $cardData): string
    {
        $jsonData = json_encode($cardData);
        return hash('sha256', $jsonData);
    }

    /**
     * Create complete card encryption package
     *
     * สร้างชุดข้อมูลเข้ารหัสแบบครบถ้วนสำหรับบัตร NFC
     */
    public function createCardEncryptionPackage(array $cardData): array
    {
        // สร้าง encryption key
        $encryptionKey = $this->generateEncryptionKey();

        // เข้ารหัสข้อมูล
        $encrypted = $this->encryptCardData($cardData, $encryptionKey);

        // สร้าง key hash สำหรับเก็บในฐานข้อมูล
        $keyHash = $this->hashEncryptionKey($encryptionKey);

        return [
            'encryption_key' => $encryptionKey, // ต้องเก็บไว้ให้ปลอดภัย
            'encrypted_data' => $encrypted['encrypted_data'],
            'encryption_key_hash' => $keyHash,
            'card_signature' => $encrypted['signature'],
            'encryption_version' => $encrypted['version'],
            'data_hash' => $encrypted['hash'],
        ];
    }

    /**
     * Re-encrypt card data with new key (for key rotation)
     */
    public function reEncryptCardData(
        string $oldEncryptedData,
        string $oldEncryptionKey,
        string $oldHash
    ): array {
        // ถอดรหัสด้วย key เก่า
        $decryptedData = $this->decryptCardData($oldEncryptedData, $oldEncryptionKey, $oldHash);

        if ($decryptedData === null) {
            throw new Exception('Failed to decrypt data with old key');
        }

        // ลบข้อมูล metadata เก่า
        unset($decryptedData['version'], $decryptedData['timestamp'], $decryptedData['nonce']);

        // เข้ารหัสใหม่ด้วย key ใหม่
        return $this->createCardEncryptionPackage($decryptedData);
    }

    /**
     * Validate card data structure
     */
    public function validateCardDataStructure(array $cardData): bool
    {
        $requiredFields = ['card_number'];

        foreach ($requiredFields as $field) {
            if (!isset($cardData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate tamper-proof token for card operations
     */
    public function generateOperationToken(string $cardNumber, string $operation, int $expiresInMinutes = 5): string
    {
        $data = [
            'card_number' => $cardNumber,
            'operation' => $operation,
            'expires_at' => now()->addMinutes($expiresInMinutes)->timestamp,
            'nonce' => Str::random(16),
        ];

        $token = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $token, config('app.key'));

        return $token . '.' . $signature;
    }

    /**
     * Verify operation token
     */
    public function verifyOperationToken(string $token, string $expectedOperation): bool
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 2) {
                return false;
            }

            [$encodedData, $signature] = $parts;

            // ตรวจสอบ signature
            $expectedSignature = hash_hmac('sha256', $encodedData, config('app.key'));

            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }

            // ถอดรหัสข้อมูล
            $data = json_decode(base64_decode($encodedData), true);

            if (!$data || !isset($data['operation'], $data['expires_at'])) {
                return false;
            }

            // ตรวจสอบ operation
            if ($data['operation'] !== $expectedOperation) {
                return false;
            }

            // ตรวจสอบว่าหมดอายุหรือยัง
            if ($data['expires_at'] < now()->timestamp) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
