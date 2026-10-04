<?php

namespace App\Services\Pos;

use App\Models\PosApiKey;
use App\Models\PosTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ยืนยันตัวตนเครื่อง POS จาก headers (X-API-Key + X-Product-Key)
 *
 * แยกออกมาจาก PosTerminalController::authenticateTerminal เดิมแบบคำต่อคำ (พฤติกรรมเหมือนเดิมทุกอย่าง)
 * เพื่อให้ endpoint ใหม่ใช้ร่วมผ่าน middleware `pos.terminal` ได้
 *
 * เงื่อนไขผ่าน: API Key active + ไม่ถูกบล็อก + ไม่หมดอายุ
 *            + Terminal (หาจาก device_id ที่ถอดจาก Product Key หรือจาก product_key ตรงๆ) ผูกกับ key นี้ + active + verified
 */
class PosTerminalAuthenticator
{
    /** ชื่อ attribute ที่ middleware ฝากเครื่อง POS ไว้บน request */
    public const REQUEST_ATTRIBUTE = 'pos_terminal';

    /**
     * Authenticate POS Terminal จาก headers — ไม่ผ่าน = null
     */
    public function authenticate(Request $request): ?PosTerminal
    {
        $apiKey = $request->header('X-API-Key');
        $productKey = $request->header('X-Product-Key');

        if (empty($apiKey) || empty($productKey)) {
            return null;
        }

        // หา API Key
        $posApiKey = PosApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->where('is_blocked', false) // ต้องไม่ถูกบล็อก
            ->first();

        if (! $posApiKey) {
            return null;
        }

        // ตรวจสอบวันหมดอายุ
        if ($posApiKey->expires_at && $posApiKey->expires_at->isPast()) {
            return null;
        }

        // Decode product key เพื่อหา device_id จริง
        $decodedData = $this->decodeProductKey($productKey);
        $deviceId = $decodedData ? $decodedData['device_id'] : null;

        // หา Terminal จาก device_id (ถ้า decode ได้) หรือ product_key
        $terminal = PosTerminal::where(function ($query) use ($productKey, $deviceId) {
            if ($deviceId) {
                $query->where('device_id', $deviceId);
            } else {
                $query->where('product_key', $productKey);
            }
        })
            ->where('api_key_id', $posApiKey->id)
            ->where('status', PosTerminal::STATUS_ACTIVE)
            ->where('is_verified', true)
            ->first();

        if ($terminal) {
            $terminal->recordSync($request->ip());
        }

        return $terminal;
    }

    /**
     * Decode Product Key เพื่อดึงข้อมูล device จริง
     *
     * Product Key format: TP-POS-XXXX-XXXX-XXXX-XXXX (Base64 encoded + encrypted)
     * เมื่อ decode แล้วจะได้: device_id, timestamp, checksum
     */
    public function decodeProductKey(string $encryptedKey): ?array
    {
        try {
            // ถ้าเป็น format TP-POS-XXXX-XXXX-XXXX-XXXX
            if (preg_match('/^TP-POS-(.+)$/', $encryptedKey, $matches)) {
                $encoded = $matches[1];
                // ลบ dash และ decode
                $encoded = str_replace('-', '', $encoded);
            } else {
                // ถ้าเป็น raw encoded string
                $encoded = $encryptedKey;
            }

            // ลอง Base64 decode
            $decoded = base64_decode($encoded, true);

            if ($decoded === false) {
                // ถ้า decode ไม่ได้ → ใช้ raw value เป็น device_id
                return [
                    'device_id' => hash('sha256', $encryptedKey.$this->getSecretKey()),
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'hash_fallback',
                ];
            }

            // ลอง XOR decrypt ด้วย secret key
            $decrypted = $this->xorDecrypt($decoded, $this->getSecretKey());

            // ลอง parse เป็น JSON
            $data = json_decode($decrypted, true);

            if ($data && isset($data['device_id'])) {
                return [
                    'device_id' => $data['device_id'],
                    'timestamp' => $data['timestamp'] ?? null,
                    'checksum' => $data['checksum'] ?? null,
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'json',
                ];
            }

            // ถ้าไม่ใช่ JSON → ใช้ decrypted string เป็น device_id
            if (! empty($decrypted) && strlen($decrypted) > 5) {
                return [
                    'device_id' => $decrypted,
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'raw',
                ];
            }

            // Fallback: hash the key
            return [
                'device_id' => hash('sha256', $encryptedKey.$this->getSecretKey()),
                'raw_key' => $encryptedKey,
                'decoded_method' => 'hash_fallback',
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to decode product key', [
                'key' => substr($encryptedKey, 0, 20).'...',
                'error' => $e->getMessage(),
            ]);

            // Fallback: hash the key
            return [
                'device_id' => hash('sha256', $encryptedKey.$this->getSecretKey()),
                'raw_key' => $encryptedKey,
                'decoded_method' => 'error_fallback',
            ];
        }
    }

    /**
     * ดึง Secret Key จาก config (ไม่ hardcode ใน code)
     */
    private function getSecretKey(): string
    {
        return config('pos.secret_key', '');
    }

    /**
     * XOR decrypt/encrypt
     */
    private function xorDecrypt(string $data, string $key): string
    {
        $result = '';
        $keyLength = strlen($key);

        for ($i = 0; $i < strlen($data); $i++) {
            $result .= chr(ord($data[$i]) ^ ord($key[$i % $keyLength]));
        }

        return $result;
    }
}
