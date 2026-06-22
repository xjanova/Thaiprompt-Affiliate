<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🌥️ (2026-05-18) Fortune Voice Storage Service
 *
 * จุดเดียวที่ตัดสินใจว่าจะเก็บไฟล์ mp3 ไว้ที่ไหน
 *   - local    : storage/app/public/fortune-voice/{file}.mp3 (default, ต้อง storage:link)
 *   - r2       : Cloudflare R2 (S3-compatible)         — แนะนำสำหรับ audio (free egress)
 *   - s3       : AWS S3
 *   - gcs      : Google Cloud Storage (ใช้ service account JSON ที่มีอยู่)
 *   - firebase : Firebase Storage (= GCS bucket {projectId}.firebasestorage.app)
 *
 * Pattern: TTS providers ยังเขียน mp3 ลง local temp file ก่อน
 *          แล้ว service นี้ค่อย "upload + delete temp" ทีหลัง
 *          → providers ไม่ต้องรู้ว่ามี cloud
 *
 * Public URL strategy:
 *   - local : Storage::disk('public')->url(...) → /storage/fortune-voice/x.mp3
 *   - r2    : ใช้ admin-config public_url (R2 public bucket URL / Cloudflare CDN)
 *             หรือ signed URL ถ้าไม่ตั้ง
 *   - s3    : ใช้ admin-config public_url หรือ signed URL
 *   - gcs   : signed URL (7 วัน) หรือ public_url ถ้า bucket = public
 *   - firebase : Firebase download URL (token-based) — ไม่ต้องตั้งค่า public bucket
 */
class FortuneVoiceStorageService
{
    public function __construct(protected FortuneTellingSetting $settings) {}

    /**
     * Driver ที่ใช้ปัจจุบัน
     */
    public function driver(): string
    {
        return $this->settings->voice_storage_driver ?: 'local';
    }

    /**
     * อ่าน config ของ driver
     *
     * @return array<string, mixed>
     */
    public function getConfig(?string $driver = null): array
    {
        $driver ??= $this->driver();
        $all = $this->settings->voice_storage_config ?: [];
        if (! is_array($all)) {
            $all = [];
        }

        // เผื่อ admin ยังไม่เซฟ → ลองอ่านจาก env เพื่อให้ default ใช้ได้
        $cfg = $all[$driver] ?? [];
        if (! is_array($cfg)) {
            $cfg = [];
        }

        return array_merge($this->envDefaults($driver), $cfg);
    }

    /**
     * Env defaults — ถ้า admin ไม่ตั้งใน DB ให้ใช้ค่าจาก .env
     *
     * @return array<string, mixed>
     */
    protected function envDefaults(string $driver): array
    {
        return match ($driver) {
            'r2' => [
                'account_id' => env('FORTUNE_VOICE_R2_ACCOUNT_ID'),
                'access_key_id' => env('FORTUNE_VOICE_R2_ACCESS_KEY_ID'),
                'secret_access_key' => env('FORTUNE_VOICE_R2_SECRET_ACCESS_KEY'),
                'bucket' => env('FORTUNE_VOICE_R2_BUCKET'),
                'public_url' => env('FORTUNE_VOICE_R2_PUBLIC_URL'),
            ],
            's3' => [
                'access_key_id' => env('FORTUNE_VOICE_S3_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
                'secret_access_key' => env('FORTUNE_VOICE_S3_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
                'region' => env('FORTUNE_VOICE_S3_REGION', env('AWS_DEFAULT_REGION', 'ap-southeast-1')),
                'bucket' => env('FORTUNE_VOICE_S3_BUCKET', env('AWS_BUCKET')),
                'endpoint' => env('FORTUNE_VOICE_S3_ENDPOINT', env('AWS_ENDPOINT')),
                'public_url' => env('FORTUNE_VOICE_S3_PUBLIC_URL', env('AWS_URL')),
            ],
            'gcs' => [
                'credentials_path' => env('FORTUNE_VOICE_GCS_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/firebase-credentials.json'))),
                'bucket' => env('FORTUNE_VOICE_GCS_BUCKET'),
                'public_url' => env('FORTUNE_VOICE_GCS_PUBLIC_URL'),
            ],
            'firebase' => [
                'credentials_path' => env('FORTUNE_VOICE_FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/firebase-credentials.json'))),
                'bucket' => env('FORTUNE_VOICE_FIREBASE_BUCKET'),
                'public_url' => null,
            ],
            default => [],
        };
    }

    /**
     * เซฟไฟล์ audio ไปยัง storage ที่กำหนด
     *
     * @param  string  $tempAbsolutePath  full path ของไฟล์ temp (จะถูกลบหลัง upload)
     * @param  string  $relativePath  path ที่ต้องการเก็บ เช่น 'fortune-voice/abc.mp3'
     * @return array{success: bool, url: ?string, disk: string, error: ?string}
     */
    public function putAudio(string $tempAbsolutePath, string $relativePath): array
    {
        if (! file_exists($tempAbsolutePath) || filesize($tempAbsolutePath) < 100) {
            return [
                'success' => false,
                'url' => null,
                'disk' => $this->driver(),
                'error' => 'temp file ว่าง หรือไม่มี: '.$tempAbsolutePath,
            ];
        }

        $driver = $this->driver();

        try {
            $url = match ($driver) {
                'local' => $this->putLocal($tempAbsolutePath, $relativePath),
                'r2', 's3' => $this->putS3($tempAbsolutePath, $relativePath, $driver),
                'gcs', 'firebase' => $this->putGcs($tempAbsolutePath, $relativePath, $driver),
                default => $this->putLocal($tempAbsolutePath, $relativePath),
            };

            // ลบ temp หลัง upload สำเร็จ (ยกเว้น local ที่ temp == ปลายทาง)
            if ($driver !== 'local' && file_exists($tempAbsolutePath)) {
                @unlink($tempAbsolutePath);
            }

            return [
                'success' => true,
                'url' => $url,
                'disk' => $driver,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('FortuneVoiceStorage: putAudio fail', [
                'driver' => $driver,
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'url' => null,
                'disk' => $driver,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ลบไฟล์ออกจาก storage
     */
    public function deleteAudio(string $relativePath, ?string $disk = null): bool
    {
        $driver = $disk ?: $this->driver();

        try {
            return match ($driver) {
                'local' => Storage::disk('public')->delete($relativePath),
                'r2', 's3' => $this->deleteS3($relativePath, $driver),
                'gcs', 'firebase' => $this->deleteGcs($relativePath, $driver),
                default => Storage::disk('public')->delete($relativePath),
            };
        } catch (\Throwable $e) {
            Log::info('FortuneVoiceStorage: deleteAudio fail (ไม่ critical)', [
                'driver' => $driver,
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * อ่าน public URL ของไฟล์ที่อยู่บน driver กำหนด (สำหรับเล่นไฟล์ที่เซฟไปแล้ว)
     */
    public function audioUrl(string $relativePath, ?string $disk = null): ?string
    {
        $driver = $disk ?: $this->driver();

        try {
            return match ($driver) {
                'local' => Storage::disk('public')->url($relativePath),
                'r2', 's3' => $this->s3Url($relativePath, $driver),
                'gcs', 'firebase' => $this->gcsUrl($relativePath, $driver),
                default => Storage::disk('public')->url($relativePath),
            };
        } catch (\Throwable $e) {
            Log::warning('FortuneVoiceStorage: audioUrl fail', [
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ทดสอบการเชื่อมต่อกับ driver — upload + read + delete test file
     *
     * @return array{success: bool, message: string, details: array}
     */
    public function testConnection(?string $driver = null, ?array $overrideConfig = null): array
    {
        $driver ??= $this->driver();

        // ถ้ามี overrideConfig → temporarily replace settings (สำหรับ test ก่อนเซฟ)
        $originalDriver = $this->settings->voice_storage_driver;
        $originalConfig = $this->settings->voice_storage_config;

        if ($overrideConfig !== null) {
            $allConfig = is_array($originalConfig) ? $originalConfig : [];
            $allConfig[$driver] = $overrideConfig;
            $this->settings->voice_storage_driver = $driver;
            $this->settings->voice_storage_config = $allConfig;
        }

        try {
            // เช็ค package ก่อน
            $availability = $this->driverAvailability($driver);
            if (! $availability['available']) {
                return [
                    'success' => false,
                    'message' => $availability['hint'],
                    'details' => $availability,
                ];
            }

            // สร้าง temp file — ต้อง >= 100 bytes เพราะ putAudio() มี floor กัน mp3 เสีย
            //   (เดิม ~28 bytes → putAudio ตีเป็น "ไฟล์ว่าง" เสมอ ไม่ว่า creds ถูกผิด)
            $testContent = 'fortune-voice-storage-connection-test '.now()->timestamp.' '.str_repeat('x', 128);
            $tempPath = storage_path('app/tmp-voice-test-'.Str::random(8).'.txt');
            @file_put_contents($tempPath, $testContent);

            $relativePath = 'fortune-voice/_test/'.Str::random(12).'.txt';
            $put = $this->putAudio($tempPath, $relativePath);

            if (! $put['success']) {
                return [
                    'success' => false,
                    'message' => '❌ Upload ล้มเหลว: '.$put['error'],
                    'details' => $put,
                ];
            }

            // ลบทันที (ไม่เก็บ test file)
            $this->deleteAudio($relativePath, $driver);

            return [
                'success' => true,
                'message' => '✅ เชื่อมต่อ '.$this->driverName($driver).' สำเร็จ — upload + delete ใช้ได้',
                'details' => [
                    'driver' => $driver,
                    'url_returned' => $put['url'],
                    'bucket' => $this->getConfig($driver)['bucket'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => '❌ Exception: '.$e->getMessage(),
                'details' => [
                    'driver' => $driver,
                    'exception' => get_class($e),
                ],
            ];
        } finally {
            // คืน settings เดิม (in-memory only)
            $this->settings->voice_storage_driver = $originalDriver;
            $this->settings->voice_storage_config = $originalConfig;
        }
    }

    /**
     * เช็คว่า driver ใช้ได้ไหม (package ติดตั้ง? credentials มี? bucket ตั้ง?)
     *
     * @return array{available: bool, hint: string, missing: array}
     */
    public function driverAvailability(?string $driver = null): array
    {
        $driver ??= $this->driver();
        $cfg = $this->getConfig($driver);
        $missing = [];

        switch ($driver) {
            case 'local':
                if (! file_exists(public_path('storage')) && ! is_link(public_path('storage'))) {
                    return [
                        'available' => false,
                        'hint' => '⚠️ ยังไม่ได้รัน `php artisan storage:link` — URL จะ 404! รันคำสั่งนี้ก่อนใช้งาน local driver',
                        'missing' => ['symlink'],
                    ];
                }

                return ['available' => true, 'hint' => 'OK', 'missing' => []];

            case 'r2':
                if (! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
                    return [
                        'available' => false,
                        'hint' => '❗ ยังไม่ได้ติดตั้ง package: `composer require league/flysystem-aws-s3-v3 "^3.0"`',
                        'missing' => ['composer_package'],
                    ];
                }
                foreach (['account_id', 'access_key_id', 'secret_access_key', 'bucket'] as $k) {
                    if (empty($cfg[$k])) {
                        $missing[] = $k;
                    }
                }
                break;

            case 's3':
                if (! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
                    return [
                        'available' => false,
                        'hint' => '❗ ยังไม่ได้ติดตั้ง package: `composer require league/flysystem-aws-s3-v3 "^3.0"`',
                        'missing' => ['composer_package'],
                    ];
                }
                foreach (['access_key_id', 'secret_access_key', 'region', 'bucket'] as $k) {
                    if (empty($cfg[$k])) {
                        $missing[] = $k;
                    }
                }
                break;

            case 'gcs':
            case 'firebase':
                if (empty($cfg['credentials_path']) || ! file_exists($cfg['credentials_path'])) {
                    return [
                        'available' => false,
                        'hint' => '❗ ไม่พบ service account JSON ที่ `'.($cfg['credentials_path'] ?? 'storage/app/firebase-credentials.json').'` (อัปโหลด credentials.json จาก Firebase/GCP console ไปที่ path นี้)',
                        'missing' => ['credentials_file'],
                    ];
                }
                if (empty($cfg['bucket'])) {
                    $missing[] = 'bucket';
                }
                break;
        }

        if (! empty($missing)) {
            return [
                'available' => false,
                'hint' => 'ยังตั้งค่าไม่ครบ: '.implode(', ', $missing),
                'missing' => $missing,
            ];
        }

        return ['available' => true, 'hint' => 'OK', 'missing' => []];
    }

    /**
     * ชื่อสวยๆ ของ driver สำหรับแสดงผล
     */
    public function driverName(string $driver): string
    {
        return match ($driver) {
            'local' => '📁 Local Disk',
            'r2' => '🟧 Cloudflare R2',
            's3' => '🟨 AWS S3',
            'gcs' => '🔵 Google Cloud Storage',
            'firebase' => '🔥 Firebase Storage',
            default => $driver,
        };
    }

    // ============================================================
    // Local
    // ============================================================

    protected function putLocal(string $tempPath, string $relativePath): string
    {
        $disk = Storage::disk('public');
        $disk->put($relativePath, file_get_contents($tempPath));

        // local: temp file = ปลายทางก็ได้ ถ้า provider เขียนตรงนั้นอยู่แล้ว
        if ($tempPath !== $disk->path($relativePath)) {
            // ไม่ลบ temp — caller จะลบเอง (TTS providers อาจเขียนตรง $absolutePath แล้ว)
        }

        return $disk->url($relativePath);
    }

    // ============================================================
    // S3-compatible (R2 + S3)
    // ============================================================

    protected function buildS3Disk(string $driver): Filesystem
    {
        if (! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            throw new \RuntimeException('ยังไม่ได้ติดตั้ง league/flysystem-aws-s3-v3 — รัน: composer require league/flysystem-aws-s3-v3 "^3.0"');
        }

        $cfg = $this->getConfig($driver);

        $diskCfg = [
            'driver' => 's3',
            'key' => $cfg['access_key_id'] ?? null,
            'secret' => $cfg['secret_access_key'] ?? null,
            'region' => $cfg['region'] ?? 'auto',
            'bucket' => $cfg['bucket'] ?? null,
            'visibility' => 'public',
            'throw' => true,
        ];

        if ($driver === 'r2') {
            $accountId = $cfg['account_id'] ?? '';
            if (empty($accountId)) {
                throw new \RuntimeException('R2: ต้องระบุ Cloudflare account_id');
            }
            $diskCfg['endpoint'] = "https://{$accountId}.r2.cloudflarestorage.com";
            $diskCfg['region'] = 'auto';
            $diskCfg['use_path_style_endpoint'] = false;
        } elseif (! empty($cfg['endpoint'])) {
            $diskCfg['endpoint'] = $cfg['endpoint'];
            $diskCfg['use_path_style_endpoint'] = true;
        }

        if (! empty($cfg['public_url'])) {
            $diskCfg['url'] = rtrim($cfg['public_url'], '/');
        }

        return Storage::build($diskCfg);
    }

    protected function putS3(string $tempPath, string $relativePath, string $driver): string
    {
        $disk = $this->buildS3Disk($driver);

        $stream = fopen($tempPath, 'rb');
        $disk->put($relativePath, $stream, [
            'visibility' => 'public',
            'ContentType' => 'audio/mpeg',
            'CacheControl' => 'public, max-age=2592000',  // 30 วัน
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $this->s3Url($relativePath, $driver);
    }

    protected function deleteS3(string $relativePath, string $driver): bool
    {
        return $this->buildS3Disk($driver)->delete($relativePath);
    }

    protected function s3Url(string $relativePath, string $driver): string
    {
        $cfg = $this->getConfig($driver);

        if (! empty($cfg['public_url'])) {
            return rtrim($cfg['public_url'], '/').'/'.ltrim($relativePath, '/');
        }

        // fallback: signed URL (1 ปี)
        $disk = $this->buildS3Disk($driver);
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($relativePath, now()->addYear());
        }

        return $disk->url($relativePath);
    }

    // ============================================================
    // GCS + Firebase Storage (raw HTTP API, ไม่ต้องลง composer package)
    // ============================================================

    /**
     * Upload via Google JSON API
     * Endpoint: POST https://storage.googleapis.com/upload/storage/v1/b/{bucket}/o?uploadType=media&name={path}
     */
    protected function putGcs(string $tempPath, string $relativePath, string $driver): string
    {
        $cfg = $this->getConfig($driver);
        $bucket = $cfg['bucket'] ?? null;
        if (empty($bucket)) {
            throw new \RuntimeException(strtoupper($driver).': bucket name ไม่ได้ตั้ง');
        }

        $accessToken = $this->getGoogleAccessToken($cfg['credentials_path']);

        $url = sprintf(
            'https://storage.googleapis.com/upload/storage/v1/b/%s/o?uploadType=media&name=%s',
            urlencode($bucket),
            rawurlencode($relativePath)
        );

        $body = file_get_contents($tempPath);
        if ($body === false) {
            throw new \RuntimeException('อ่าน temp file ไม่ได้: '.$tempPath);
        }

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=2592000',
            ])
            ->withBody($body, 'audio/mpeg')
            ->timeout(60)
            ->post($url);

        if (! $response->successful()) {
            $msg = $response->json('error.message') ?? mb_substr($response->body(), 0, 200);
            throw new \RuntimeException(strtoupper($driver).' upload fail: HTTP '.$response->status().' — '.$msg);
        }

        // Firebase Storage: set firebaseStorageDownloadTokens เพื่อ generate download URL
        if ($driver === 'firebase') {
            return $this->setFirebaseDownloadToken($bucket, $relativePath, $accessToken);
        }

        // GCS: ใช้ public_url ถ้ามี (admin ตั้ง bucket = public) หรือ signed URL
        return $this->gcsUrl($relativePath, $driver, $accessToken);
    }

    protected function deleteGcs(string $relativePath, string $driver): bool
    {
        try {
            $cfg = $this->getConfig($driver);
            $bucket = $cfg['bucket'] ?? null;
            if (empty($bucket)) {
                return false;
            }

            $accessToken = $this->getGoogleAccessToken($cfg['credentials_path']);

            $url = sprintf(
                'https://storage.googleapis.com/storage/v1/b/%s/o/%s',
                urlencode($bucket),
                rawurlencode($relativePath)
            );

            $response = Http::withToken($accessToken)->timeout(30)->delete($url);

            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function gcsUrl(string $relativePath, string $driver, ?string $accessToken = null): string
    {
        $cfg = $this->getConfig($driver);
        $bucket = $cfg['bucket'] ?? '';

        if (! empty($cfg['public_url'])) {
            return rtrim($cfg['public_url'], '/').'/'.ltrim($relativePath, '/');
        }

        // Firebase: token-based download URL (ถ้ามี token เก็บไว้)
        // GCS: signed URL หรือ public URL standard
        // Default: standard public URL (bucket ต้องเปิด public access)
        return sprintf(
            'https://storage.googleapis.com/%s/%s',
            $bucket,
            ltrim($relativePath, '/')
        );
    }

    /**
     * Firebase Storage download URL = bucket + path + token
     * ตั้ง metadata.firebaseStorageDownloadTokens = uuid → URL คงที่
     */
    protected function setFirebaseDownloadToken(string $bucket, string $relativePath, string $accessToken): string
    {
        $token = (string) Str::uuid();

        $url = sprintf(
            'https://storage.googleapis.com/storage/v1/b/%s/o/%s',
            urlencode($bucket),
            rawurlencode($relativePath)
        );

        try {
            Http::withToken($accessToken)
                ->asJson()
                ->timeout(30)
                ->patch($url, [
                    'metadata' => [
                        'firebaseStorageDownloadTokens' => $token,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::info('Firebase setDownloadToken fail (ใช้ standard URL แทน)', [
                'error' => $e->getMessage(),
            ]);
        }

        // Firebase download URL format
        return sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media&token=%s',
            $bucket,
            rawurlencode($relativePath),
            $token
        );
    }

    /**
     * แปลง service account JSON → OAuth2 access token (JWT bearer flow)
     * Cache 50 นาที (token valid 60 นาที)
     */
    protected function getGoogleAccessToken(string $credentialsPath): string
    {
        if (! file_exists($credentialsPath)) {
            throw new \RuntimeException('Service account JSON หายไป: '.$credentialsPath);
        }

        $cacheKey = 'fortune_voice_gcs_token_'.md5($credentialsPath);
        $cached = cache()->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $json = json_decode(file_get_contents($credentialsPath), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new \RuntimeException('Service account JSON ไม่ถูกฟอร์แมต — ต้องมี client_email + private_key');
        }

        // สร้าง JWT
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $claim = [
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlEncode = fn ($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        $signingInput = $base64UrlEncode(json_encode($header)).'.'.$base64UrlEncode(json_encode($claim));

        $signature = '';
        $signed = openssl_sign(
            $signingInput,
            $signature,
            $json['private_key'],
            OPENSSL_ALGO_SHA256
        );

        if (! $signed) {
            throw new \RuntimeException('JWT sign ล้มเหลว — private_key ใน service account JSON อาจเสีย');
        }

        $jwt = $signingInput.'.'.$base64UrlEncode($signature);

        // แลก JWT → access token
        $response = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OAuth exchange fail: HTTP '.$response->status().' — '.mb_substr($response->body(), 0, 200));
        }

        $token = $response->json('access_token');
        if (empty($token)) {
            throw new \RuntimeException('OAuth response ไม่มี access_token: '.mb_substr($response->body(), 0, 200));
        }

        cache()->put($cacheKey, $token, now()->addMinutes(50));

        return $token;
    }

    /**
     * นับไฟล์ + ขนาดรวมของ voice files (สำหรับ dashboard)
     *
     * @return array{count: int, total_bytes: int, error: ?string}
     */
    public function stats(): array
    {
        $driver = $this->driver();

        try {
            if ($driver === 'local') {
                $dir = Storage::disk('public')->path('fortune-voice');
                if (! is_dir($dir)) {
                    return ['count' => 0, 'total_bytes' => 0, 'error' => null];
                }
                $count = 0;
                $bytes = 0;
                foreach (glob($dir.'/*.mp3') ?: [] as $f) {
                    $count++;
                    $bytes += filesize($f);
                }

                return ['count' => $count, 'total_bytes' => $bytes, 'error' => null];
            }

            // Cloud: ไม่ list เพื่อกัน cost — return 0 (admin ดูที่ console ของ provider แทน)
            return ['count' => 0, 'total_bytes' => 0, 'error' => 'stats เฉพาะ local — ดู cloud usage ใน provider console'];
        } catch (\Throwable $e) {
            return ['count' => 0, 'total_bytes' => 0, 'error' => $e->getMessage()];
        }
    }
}
