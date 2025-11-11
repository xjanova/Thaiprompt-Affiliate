<?php

namespace App\Services;

use App\Models\User;
use App\Models\MlmProspect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Duplicate Detection Service
 *
 * Prevents duplicate registrations by checking:
 * - Email addresses
 * - Phone numbers
 * - LINE user IDs
 * - Thai ID card numbers
 *
 * Uses fuzzy matching and caching for optimal performance
 */
class DuplicateDetectionService
{
    /**
     * Cache TTL in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Check for duplicate email
     *
     * @param string $email
     * @param int|null $excludeUserId Exclude this user ID from check
     * @return array
     */
    public function checkDuplicateEmail(string $email, ?int $excludeUserId = null): array
    {
        $email = strtolower(trim($email));

        // Check cache first
        $cacheKey = "duplicate_check:email:{$email}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null && (!$excludeUserId || $cached['user_id'] !== $excludeUserId)) {
            return $cached;
        }

        // Check in users table
        $query = User::where('email', $email);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $existingUser = $query->first();

        $result = [
            'exists' => !is_null($existingUser),
            'user_id' => $existingUser?->id,
            'user_name' => $existingUser?->name,
            'registered_at' => $existingUser?->created_at?->format('Y-m-d H:i:s'),
            'message' => $existingUser
                ? 'อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น'
                : null,
        ];

        // Cache the result
        if ($existingUser) {
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
        }

        return $result;
    }

    /**
     * Check for duplicate phone number
     *
     * @param string $phone
     * @param int|null $excludeUserId
     * @return array
     */
    public function checkDuplicatePhone(string $phone, ?int $excludeUserId = null): array
    {
        // Normalize phone number (remove spaces, dashes, etc.)
        $normalized = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Try multiple formats
        $phoneVariants = $this->generatePhoneVariants($normalized);

        $cacheKey = "duplicate_check:phone:{$normalized}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null && (!$excludeUserId || $cached['user_id'] !== $excludeUserId)) {
            return $cached;
        }

        // Check in users table
        $query = User::whereIn('phone', $phoneVariants);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $existingUser = $query->first();

        $result = [
            'exists' => !is_null($existingUser),
            'user_id' => $existingUser?->id,
            'user_name' => $existingUser?->name,
            'phone' => $existingUser?->phone,
            'registered_at' => $existingUser?->created_at?->format('Y-m-d H:i:s'),
            'message' => $existingUser
                ? 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว กรุณาใช้เบอร์อื่น'
                : null,
        ];

        if ($existingUser) {
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
        }

        return $result;
    }

    /**
     * Check for duplicate LINE user ID
     *
     * @param string $lineUserId
     * @param int|null $excludeUserId
     * @return array
     */
    public function checkDuplicateLineId(string $lineUserId, ?int $excludeUserId = null): array
    {
        $cacheKey = "duplicate_check:line:{$lineUserId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null && (!$excludeUserId || $cached['user_id'] !== $excludeUserId)) {
            return $cached;
        }

        // Check in users table
        $query = User::where('line_user_id', $lineUserId);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $existingUser = $query->first();

        $result = [
            'exists' => !is_null($existingUser),
            'user_id' => $existingUser?->id,
            'user_name' => $existingUser?->name,
            'line_display_name' => $existingUser?->line_display_name,
            'registered_at' => $existingUser?->created_at?->format('Y-m-d H:i:s'),
            'message' => $existingUser
                ? 'บัญชี LINE นี้ถูกใช้งานแล้ว หากคุณเป็นเจ้าของบัญชี กรุณาเข้าสู่ระบบ'
                : null,
        ];

        if ($existingUser) {
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
        }

        return $result;
    }

    /**
     * Check for duplicate Thai ID card
     *
     * @param string $idCard
     * @param int|null $excludeUserId
     * @return array
     */
    public function checkDuplicateThaiId(string $idCard, ?int $excludeUserId = null): array
    {
        // Normalize ID card (remove spaces and dashes)
        $normalized = preg_replace('/[\s\-]/', '', $idCard);

        $cacheKey = "duplicate_check:thaiid:{$normalized}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null && (!$excludeUserId || $cached['user_id'] !== $excludeUserId)) {
            return $cached;
        }

        // Check in users table
        $query = User::where('id_card_number', $normalized);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $existingUser = $query->first();

        $result = [
            'exists' => !is_null($existingUser),
            'user_id' => $existingUser?->id,
            'user_name' => $existingUser?->name,
            'registered_at' => $existingUser?->created_at?->format('Y-m-d H:i:s'),
            'message' => $existingUser
                ? 'เลขบัตรประชาชนนี้ถูกใช้งานแล้ว'
                : null,
        ];

        if ($existingUser) {
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
        }

        return $result;
    }

    /**
     * Comprehensive duplicate check (all fields)
     *
     * @param array $data ['email', 'phone', 'line_user_id', 'id_card_number']
     * @param int|null $excludeUserId
     * @return array
     */
    public function checkAllDuplicates(array $data, ?int $excludeUserId = null): array
    {
        $duplicates = [];
        $hasDuplicates = false;

        // Check email
        if (!empty($data['email'])) {
            $emailCheck = $this->checkDuplicateEmail($data['email'], $excludeUserId);
            if ($emailCheck['exists']) {
                $duplicates['email'] = $emailCheck;
                $hasDuplicates = true;
            }
        }

        // Check phone
        if (!empty($data['phone'])) {
            $phoneCheck = $this->checkDuplicatePhone($data['phone'], $excludeUserId);
            if ($phoneCheck['exists']) {
                $duplicates['phone'] = $phoneCheck;
                $hasDuplicates = true;
            }
        }

        // Check LINE user ID
        if (!empty($data['line_user_id'])) {
            $lineCheck = $this->checkDuplicateLineId($data['line_user_id'], $excludeUserId);
            if ($lineCheck['exists']) {
                $duplicates['line_user_id'] = $lineCheck;
                $hasDuplicates = true;
            }
        }

        // Check Thai ID card
        if (!empty($data['id_card_number'])) {
            $idCheck = $this->checkDuplicateThaiId($data['id_card_number'], $excludeUserId);
            if ($idCheck['exists']) {
                $duplicates['id_card_number'] = $idCheck;
                $hasDuplicates = true;
            }
        }

        return [
            'has_duplicates' => $hasDuplicates,
            'duplicates' => $duplicates,
            'messages' => array_column($duplicates, 'message'),
        ];
    }

    /**
     * Check if prospect is already in signup process
     *
     * @param string $lineUserId
     * @return array
     */
    public function checkProspectInProgress(string $lineUserId): array
    {
        $prospect = MlmProspect::where('line_user_id', $lineUserId)
            ->whereIn('status', ['in_progress', 'pending'])
            ->where('conversation_expired', false)
            ->first();

        return [
            'in_progress' => !is_null($prospect),
            'prospect' => $prospect,
            'progress_percent' => $prospect?->conversation_progress_percent,
            'current_step' => $prospect?->conversation_step,
            'message' => $prospect
                ? "คุณกำลังอยู่ในขั้นตอนการสมัครสมาชิก (ทำไปแล้ว {$prospect->conversation_progress_percent}%)"
                : null,
        ];
    }

    /**
     * Clear duplicate detection cache for a specific field
     *
     * @param string $type 'email', 'phone', 'line', 'thaiid'
     * @param string $value
     * @return void
     */
    public function clearCache(string $type, string $value): void
    {
        $cacheKey = match($type) {
            'email' => "duplicate_check:email:" . strtolower(trim($value)),
            'phone' => "duplicate_check:phone:" . preg_replace('/[\s\-\(\)]/', '', $value),
            'line' => "duplicate_check:line:{$value}",
            'thaiid' => "duplicate_check:thaiid:" . preg_replace('/[\s\-]/', '', $value),
            default => null,
        };

        if ($cacheKey) {
            Cache::forget($cacheKey);
            Log::debug('Duplicate detection cache cleared', ['type' => $type, 'key' => $cacheKey]);
        }
    }

    /**
     * Clear all duplicate detection caches for a user
     *
     * @param User $user
     * @return void
     */
    public function clearUserCache(User $user): void
    {
        if ($user->email) {
            $this->clearCache('email', $user->email);
        }

        if ($user->phone) {
            $this->clearCache('phone', $user->phone);
        }

        if ($user->line_user_id) {
            $this->clearCache('line', $user->line_user_id);
        }

        if ($user->id_card_number) {
            $this->clearCache('thaiid', $user->id_card_number);
        }

        Log::info('All duplicate detection caches cleared for user', ['user_id' => $user->id]);
    }

    /**
     * Generate phone number variants for matching
     *
     * @param string $phone
     * @return array
     */
    private function generatePhoneVariants(string $phone): array
    {
        $variants = [$phone];

        // If starts with 0, add +66 variant
        if (str_starts_with($phone, '0')) {
            $variants[] = '+66' . substr($phone, 1);
            $variants[] = '66' . substr($phone, 1);
        }

        // If starts with +66 or 66, add 0 variant
        if (str_starts_with($phone, '+66')) {
            $variants[] = '0' . substr($phone, 3);
            $variants[] = substr($phone, 1); // Remove +
        } elseif (str_starts_with($phone, '66') && strlen($phone) === 11) {
            $variants[] = '0' . substr($phone, 2);
            $variants[] = '+' . $phone;
        }

        return array_unique($variants);
    }

    /**
     * Get duplicate detection statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'users_with_line' => User::whereNotNull('line_user_id')->count(),
            'users_with_phone' => User::whereNotNull('phone')->count(),
            'users_with_thai_id' => User::whereNotNull('id_card_number')->count(),
            'duplicate_emails' => User::select('email')
                ->whereNotNull('email')
                ->groupBy('email')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
            'duplicate_phones' => User::select('phone')
                ->whereNotNull('phone')
                ->groupBy('phone')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
        ];
    }
}
