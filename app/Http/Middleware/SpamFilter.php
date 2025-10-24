<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CloudflareConfig;
use App\Services\SpamDetectionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SpamFilter
{
    protected $spamDetection;

    public function __construct(SpamDetectionService $spamDetection)
    {
        $this->spamDetection = $spamDetection;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        // Get Cloudflare configuration
        $config = CloudflareConfig::where('is_active', true)->first();

        if (!$config) {
            return $next($request);
        }

        // Check if spam filtering is enabled for this type
        $filterEnabled = false;
        switch ($type) {
            case 'comment':
            case 'review':
                $filterEnabled = $config->filter_comments;
                break;
            case 'order':
                $filterEnabled = $config->filter_orders;
                break;
            case 'contact':
                $filterEnabled = $config->filter_contact_forms;
                break;
            case 'registration':
                $filterEnabled = $config->filter_registrations;
                break;
        }

        if (!$filterEnabled) {
            return $next($request);
        }

        // Rate limiting check
        $key = $this->getRateLimitKey($request, $type);
        $maxAttempts = $this->getMaxAttempts($config, $type);
        $decayMinutes = $this->getDecayMinutes($config, $type);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'type' => $type,
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        // Spam content detection
        $content = $this->extractContent($request, $type);
        if ($content && $this->spamDetection->isSpam($content, $type)) {
            RateLimiter::hit($key, $decayMinutes * 60);

            Log::warning('Spam content detected', [
                'ip' => $request->ip(),
                'type' => $type,
                'content' => substr($content, 0, 100)
            ]);

            return response()->json([
                'error' => 'Your submission appears to contain spam. Please review and try again.'
            ], 422);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }

    /**
     * Get rate limit key
     *
     * @param Request $request
     * @param string $type
     * @return string
     */
    private function getRateLimitKey(Request $request, string $type): string
    {
        $identifier = $request->user()
            ? 'user:' . $request->user()->id
            : 'ip:' . $request->ip();

        return "spam_filter:{$type}:{$identifier}";
    }

    /**
     * Get max attempts for rate limiting
     *
     * @param CloudflareConfig $config
     * @param string $type
     * @return int
     */
    private function getMaxAttempts(CloudflareConfig $config, string $type): int
    {
        $limits = $config->rate_limit_rules ?? [];
        return $limits[$type]['max_attempts'] ?? 10;
    }

    /**
     * Get decay minutes for rate limiting
     *
     * @param CloudflareConfig $config
     * @param string $type
     * @return int
     */
    private function getDecayMinutes(CloudflareConfig $config, string $type): int
    {
        $limits = $config->rate_limit_rules ?? [];
        return $limits[$type]['decay_minutes'] ?? 60;
    }

    /**
     * Extract content to check for spam
     *
     * @param Request $request
     * @param string $type
     * @return string|null
     */
    private function extractContent(Request $request, string $type): ?string
    {
        switch ($type) {
            case 'comment':
            case 'review':
                return $request->input('comment') ?? $request->input('review') ?? $request->input('content');
            case 'order':
                return $request->input('notes') ?? $request->input('special_instructions');
            case 'contact':
                return $request->input('message');
            case 'registration':
                return implode(' ', [
                    $request->input('name', ''),
                    $request->input('email', ''),
                    $request->input('phone', '')
                ]);
            default:
                return null;
        }
    }
}
