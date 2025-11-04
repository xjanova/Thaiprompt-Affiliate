<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * File Integrity Service
 * ตรวจสอบความถูกต้องของไฟล์เพื่อป้องกันการแก้ไขโค้ดข้าม license check
 */
class IntegrityService
{
    /**
     * Critical files that must not be modified
     */
    protected array $criticalFiles = [
        'app/Services/LicenseService.php',
        'app/Services/IntegrityService.php',
        'app/Console/Commands/LicenseCheckCommand.php',
        'app/Console/Commands/LicenseActivateCommand.php',
        'app/Console/Commands/UpdateCommand.php',
        'config/license.php',
    ];

    /**
     * Verify file integrity
     */
    public function verify(): array
    {
        $cacheKey = 'file_integrity_check';
        $cacheDuration = 3600; // 1 hour

        // Check cache first
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached['valid']) {
                return $cached;
            }
        }

        $violations = [];

        foreach ($this->criticalFiles as $file) {
            $fullPath = base_path($file);

            if (!File::exists($fullPath)) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'file_missing',
                    'message' => 'Critical file is missing',
                ];
                continue;
            }

            // Check for suspicious modifications
            if ($this->hasSuspiciousContent($fullPath)) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'suspicious_modification',
                    'message' => 'File contains suspicious modifications',
                ];
            }
        }

        $result = [
            'valid' => empty($violations),
            'violations' => $violations,
            'checked_at' => now()->toDateTimeString(),
        ];

        // Cache only valid results
        if ($result['valid']) {
            Cache::put($cacheKey, $result, $cacheDuration);
        } else {
            // Log violations
            Log::critical('File integrity violations detected', [
                'violations' => $violations,
                'ip' => request()->ip(),
            ]);
        }

        return $result;
    }

    /**
     * Check for suspicious content in file
     */
    protected function hasSuspiciousContent(string $filePath): bool
    {
        $content = File::get($filePath);

        // Patterns that indicate tampering
        $suspiciousPatterns = [
            // Comments out license check
            '/\/\/\s*(.*license.*check|.*validate.*license)/i',
            '/\/\*\s*(.*license.*check|.*validate.*license).*\*\//is',

            // Returns true without checking
            '/return\s+true;\s*\/\/\s*(bypass|skip|ignore).*license/i',

            // Commented out validation
            '/\/\/.*licenseService->validate\(\)/i',

            // Config developer_mode (we removed this)
            '/developer_mode.*true/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate file checksum
     */
    public function calculateChecksum(string $file): string
    {
        $fullPath = base_path($file);

        if (!File::exists($fullPath)) {
            return '';
        }

        return hash_file('sha256', $fullPath);
    }

    /**
     * Generate checksums for all critical files
     */
    public function generateChecksums(): array
    {
        $checksums = [];

        foreach ($this->criticalFiles as $file) {
            $checksums[$file] = $this->calculateChecksum($file);
        }

        return $checksums;
    }

    /**
     * Verify checksums against known good values
     */
    public function verifyChecksums(array $knownChecksums): array
    {
        $violations = [];

        foreach ($knownChecksums as $file => $knownChecksum) {
            $currentChecksum = $this->calculateChecksum($file);

            if ($currentChecksum !== $knownChecksum) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'checksum_mismatch',
                    'message' => 'File has been modified',
                    'expected' => $knownChecksum,
                    'actual' => $currentChecksum,
                ];
            }
        }

        return $violations;
    }

    /**
     * Clear integrity cache (force re-check)
     */
    public function clearCache(): void
    {
        Cache::forget('file_integrity_check');
    }
}
