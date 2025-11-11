<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * LINE Token Service
 *
 * Handles secure storage, encryption, and caching of LINE access tokens
 * with Redis TTL support for optimal performance and security.
 */
class LineTokenService
{
    // Cache TTL in seconds (LINE tokens typically expire in ~30 days, we use 7 days for safety)
    private const CACHE_TTL = 604800; // 7 days

    // Cache key prefix
    private const CACHE_PREFIX = 'line_token:';

    /**
     * Store encrypted access token for user
     *
     * @param User $user
     * @param string $accessToken Plain text access token
     * @param int|null $expiresIn Token expiration time in seconds (optional)
     * @return bool
     */
    public function storeAccessToken(User $user, string $accessToken, ?int $expiresIn = null): bool
    {
        try {
            // Encrypt the token
            $encryptedToken = Crypt::encryptString($accessToken);

            // Store in database
            $user->update(['line_access_token' => $encryptedToken]);

            // Cache the plain token with TTL (faster access)
            $ttl = $expiresIn ?? self::CACHE_TTL;
            Cache::put(
                $this->getCacheKey($user->id),
                $accessToken,
                now()->addSeconds($ttl)
            );

            Log::info('LINE access token stored securely', [
                'user_id' => $user->id,
                'ttl' => $ttl,
                'cached' => true,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to store LINE access token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get decrypted access token for user
     *
     * @param User $user
     * @return string|null
     */
    public function getAccessToken(User $user): ?string
    {
        try {
            // Try cache first (fastest)
            $cachedToken = Cache::get($this->getCacheKey($user->id));
            if ($cachedToken) {
                Log::debug('LINE token retrieved from cache', ['user_id' => $user->id]);
                return $cachedToken;
            }

            // If not in cache, decrypt from database
            if (!$user->line_access_token) {
                return null;
            }

            $decryptedToken = Crypt::decryptString($user->line_access_token);

            // Re-cache for next time
            Cache::put(
                $this->getCacheKey($user->id),
                $decryptedToken,
                now()->addSeconds(self::CACHE_TTL)
            );

            Log::debug('LINE token decrypted from database and cached', ['user_id' => $user->id]);

            return $decryptedToken;
        } catch (Exception $e) {
            Log::error('Failed to retrieve LINE access token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify if token is valid (not expired)
     *
     * @param User $user
     * @return bool
     */
    public function verifyToken(User $user): bool
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            return false;
        }

        // Use LineService to verify with LINE API
        try {
            $lineService = new LineService();
            return $lineService->verifyToken($token);
        } catch (Exception $e) {
            Log::warning('LINE token verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refresh access token if expired
     *
     * @param User $user
     * @param string $refreshToken
     * @return bool
     */
    public function refreshAccessToken(User $user, string $refreshToken): bool
    {
        try {
            // Call LINE OAuth to refresh token
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post('https://access.line.me/oauth2/v2.1/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => config('services.line.client_id'),
                    'client_secret' => config('services.line.client_secret'),
                ]);

            if (!$response->successful()) {
                throw new Exception('Token refresh failed: ' . $response->body());
            }

            $data = $response->json();
            $newAccessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? null;

            // Store new token
            return $this->storeAccessToken($user, $newAccessToken, $expiresIn);
        } catch (Exception $e) {
            Log::error('Failed to refresh LINE access token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Revoke and delete user's access token
     *
     * @param User $user
     * @return bool
     */
    public function revokeAccessToken(User $user): bool
    {
        try {
            $token = $this->getAccessToken($user);

            if ($token) {
                // Revoke token on LINE side
                \Illuminate\Support\Facades\Http::asForm()
                    ->post('https://access.line.me/oauth2/v2.1/revoke', [
                        'access_token' => $token,
                        'client_id' => config('services.line.client_id'),
                        'client_secret' => config('services.line.client_secret'),
                    ]);
            }

            // Delete from database
            $user->update(['line_access_token' => null]);

            // Delete from cache
            Cache::forget($this->getCacheKey($user->id));

            Log::info('LINE access token revoked', ['user_id' => $user->id]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to revoke LINE access token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if user has valid token in cache or database
     *
     * @param User $user
     * @return bool
     */
    public function hasValidToken(User $user): bool
    {
        // Check cache first
        if (Cache::has($this->getCacheKey($user->id))) {
            return true;
        }

        // Check database
        if ($user->line_access_token) {
            // Try to decrypt to verify it's valid
            try {
                $token = $this->getAccessToken($user);
                return !empty($token);
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get cache key for user's token
     *
     * @param int $userId
     * @return string
     */
    private function getCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId;
    }

    /**
     * Bulk cleanup expired tokens from cache
     * Should be called periodically via scheduled task
     *
     * @return int Number of tokens cleaned up
     */
    public function cleanupExpiredTokens(): int
    {
        $count = 0;

        try {
            // Get all users with LINE tokens
            $users = User::whereNotNull('line_access_token')->get();

            foreach ($users as $user) {
                // Check if token is still valid
                if (!$this->verifyToken($user)) {
                    // Token is expired or invalid, remove it
                    $this->revokeAccessToken($user);
                    $count++;
                }
            }

            Log::info('LINE token cleanup completed', ['cleaned' => $count]);
        } catch (Exception $e) {
            Log::error('Failed to cleanup expired tokens', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Migrate existing plain text tokens to encrypted format
     * Should be run once during deployment
     *
     * @return array ['success' => int, 'failed' => int]
     */
    public function migrateExistingTokens(): array
    {
        $stats = ['success' => 0, 'failed' => 0];

        try {
            // Find users with tokens that are not encrypted yet
            $users = User::whereNotNull('line_access_token')->get();

            foreach ($users as $user) {
                try {
                    // Check if already encrypted by trying to decrypt
                    $currentToken = $user->line_access_token;

                    try {
                        // If this succeeds, it's already encrypted
                        Crypt::decryptString($currentToken);
                        continue; // Skip, already encrypted
                    } catch (Exception $e) {
                        // Not encrypted yet, encrypt it
                        $encryptedToken = Crypt::encryptString($currentToken);
                        $user->update(['line_access_token' => $encryptedToken]);

                        // Cache the plain token
                        Cache::put(
                            $this->getCacheKey($user->id),
                            $currentToken,
                            now()->addSeconds(self::CACHE_TTL)
                        );

                        $stats['success']++;
                    }
                } catch (Exception $e) {
                    $stats['failed']++;
                    Log::error('Failed to migrate token for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('LINE token migration completed', $stats);
        } catch (Exception $e) {
            Log::error('Token migration failed', ['error' => $e->getMessage()]);
        }

        return $stats;
    }
}
