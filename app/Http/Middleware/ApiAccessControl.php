<?php

namespace App\Http\Middleware;

use App\Models\ApiEndpoint;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\ApiQuota;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessControl
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // ดึง API key จาก header
        $apiKeyValue = $request->header('X-API-Key') ?? $request->bearerToken();

        if (!$apiKeyValue) {
            return $this->errorResponse('API key is required', 401);
        }

        // ตรวจสอบ API key
        $apiKey = ApiKey::verify($apiKeyValue);

        if (!$apiKey) {
            return $this->errorResponse('Invalid or expired API key', 401);
        }

        // ตรวจสอบ IP address
        if (!$apiKey->isIpAllowed($request->ip())) {
            $this->logUsage($apiKey, $request, 403, $startTime, 'IP not allowed');
            return $this->errorResponse('IP address not allowed', 403);
        }

        // ตรวจสอบว่า endpoint นี้เปิดใช้งานหรือไม่
        $endpoint = $this->findEndpoint($request);

        if ($endpoint && !$endpoint->is_active) {
            $this->logUsage($apiKey, $request, 403, $startTime, 'Endpoint disabled', $endpoint->id);
            return $this->errorResponse('This API endpoint is currently disabled', 403);
        }

        // ตรวจสอบว่า API key สามารถเข้าถึง endpoint นี้ได้หรือไม่
        if ($endpoint && !$apiKey->canAccessEndpoint($endpoint->id)) {
            $this->logUsage($apiKey, $request, 403, $startTime, 'Access denied', $endpoint->id);
            return $this->errorResponse('Access denied to this endpoint', 403);
        }

        // ตรวจสอบ scope (สำหรับ write operations)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (!$apiKey->hasScope('write')) {
                $this->logUsage($apiKey, $request, 403, $startTime, 'Write permission required', $endpoint?->id);
                return $this->errorResponse('Write permission required', 403);
            }
        }

        // ตรวจสอบโควต้ารายเดือน
        if ($apiKey->isQuotaExceeded()) {
            $this->logUsage($apiKey, $request, 429, $startTime, 'Monthly quota exceeded', $endpoint?->id);
            return $this->errorResponse('Monthly quota exceeded', 429);
        }

        // ตรวจสอบ rate limiting
        $rateLimitCheck = $this->checkRateLimits($apiKey, $endpoint);
        if ($rateLimitCheck !== true) {
            $this->logUsage($apiKey, $request, 429, $startTime, $rateLimitCheck, $endpoint?->id);
            return $this->errorResponse($rateLimitCheck, 429);
        }

        // เก็บข้อมูลใน request เพื่อใช้ในภายหลัง
        $request->merge([
            '_api_key' => $apiKey,
            '_api_endpoint' => $endpoint,
            '_api_start_time' => $startTime,
        ]);

        // ดำเนินการ request
        $response = $next($request);

        // Log การใช้งาน
        $this->logUsage(
            $apiKey,
            $request,
            $response->status(),
            $startTime,
            null,
            $endpoint?->id
        );

        // เพิ่มจำนวนการใช้งาน
        $apiKey->incrementUsage();

        // เพิ่ม response headers
        $response->headers->set('X-RateLimit-Remaining', $this->getRemainingRequests($apiKey, $endpoint));
        $response->headers->set('X-API-Key-ID', $apiKey->id);

        return $response;
    }

    /**
     * หา endpoint จาก request
     */
    protected function findEndpoint(Request $request): ?ApiEndpoint
    {
        $path = $request->path();
        $method = $request->method();

        return ApiEndpoint::where('path', $path)
            ->where('method', $method)
            ->first();
    }

    /**
     * ตรวจสอบ rate limiting ทุกระดับ
     */
    protected function checkRateLimits(ApiKey $apiKey, ?ApiEndpoint $endpoint): string|bool
    {
        // ลำดับความสำคัญ: API Key > Endpoint
        $limits = [
            'minute' => $apiKey->rate_limit_per_minute ?? $endpoint?->rate_limit_per_minute ?? 60,
            'hour' => $apiKey->rate_limit_per_hour ?? $endpoint?->rate_limit_per_hour ?? 1000,
            'day' => $apiKey->rate_limit_per_day ?? $endpoint?->rate_limit_per_day ?? 10000,
        ];

        foreach ($limits as $periodType => $limit) {
            $quota = ApiQuota::getOrCreateForPeriod($apiKey->id, $periodType, $limit);

            if ($quota->isLimitExceeded()) {
                return "Rate limit exceeded for {$periodType}";
            }

            $quota->incrementCount();
        }

        return true;
    }

    /**
     * Log การใช้งาน API
     */
    protected function logUsage(
        ApiKey $apiKey,
        Request $request,
        int $responseCode,
        float $startTime,
        ?string $errorMessage = null,
        ?int $endpointId = null
    ): void {
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        ApiUsageLog::create([
            'api_key_id' => $apiKey->id,
            'api_endpoint_id' => $endpointId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_code' => $responseCode,
            'response_time_ms' => $responseTime,
            'request_payload' => $this->sanitizePayload($request->all()),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * ทำความสะอาด payload (ลบข้อมูลที่ sensitive)
     */
    protected function sanitizePayload(array $data): string
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'credit_card'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive)) {
                $value = '[REDACTED]';
            }
        });

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * คำนวณจำนวน requests ที่เหลือ
     */
    protected function getRemainingRequests(ApiKey $apiKey, ?ApiEndpoint $endpoint): int
    {
        $limit = $apiKey->rate_limit_per_minute ?? $endpoint?->rate_limit_per_minute ?? 60;
        $quota = ApiQuota::getOrCreateForPeriod($apiKey->id, 'minute', $limit);

        return $quota->remaining ?? 0;
    }

    /**
     * Response error
     */
    protected function errorResponse(string $message, int $code): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'type' => $this->getErrorType($code),
            ],
        ], $code);
    }

    /**
     * Get error type
     */
    protected function getErrorType(int $code): string
    {
        return match ($code) {
            401 => 'authentication_error',
            403 => 'authorization_error',
            429 => 'rate_limit_error',
            default => 'api_error',
        };
    }
}
