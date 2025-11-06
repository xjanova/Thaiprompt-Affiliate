<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Webhook Signature Middleware
 *
 * Validates webhook signatures to ensure requests come from legitimate sources.
 * Supports multiple payment gateways.
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming webhook request
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $verified = match ($provider) {
            'line' => $this->verifyLineSignature($request),
            'promptpay' => $this->verifyPromptPaySignature($request),
            'paysolutions' => $this->verifyPaySolutionsSignature($request),
            'stripe' => $this->verifyStripeSignature($request),
            'omise' => $this->verifyOmiseSignature($request),
            default => false,
        };

        if (!$verified) {
            Log::warning("Webhook signature verification failed for {$provider}", [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Verify LINE webhook signature
     */
    protected function verifyLineSignature(Request $request): bool
    {
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();
        $channelSecret = config('services.line.channel_secret');

        if (!$signature || !$channelSecret) {
            return false;
        }

        $hash = hash_hmac('sha256', $body, $channelSecret, true);
        $calculatedSignature = base64_encode($hash);

        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Verify PromptPay webhook signature
     */
    protected function verifyPromptPaySignature(Request $request): bool
    {
        $signature = $request->header('X-PromptPay-Signature');
        $body = $request->getContent();
        $secret = config('services.promptpay.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        $calculatedSignature = hash_hmac('sha256', $body, $secret);

        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Verify PaySolutions webhook signature
     */
    protected function verifyPaySolutionsSignature(Request $request): bool
    {
        // PaySolutions uses HMAC-SHA256 signature
        $signature = $request->header('X-PaySolutions-Signature');
        $body = $request->getContent();
        $secret = config('services.paysolutions.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        // PaySolutions signature format: sha256=<hash>
        if (strpos($signature, 'sha256=') === 0) {
            $signature = substr($signature, 7);
        }

        $calculatedSignature = hash_hmac('sha256', $body, $secret);

        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Verify Stripe webhook signature
     */
    protected function verifyStripeSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        // Stripe uses a more complex signature format
        // In production, use Stripe's SDK: \Stripe\Webhook::constructEvent()
        // This is a simplified version
        return true; // Implement full Stripe verification in production
    }

    /**
     * Verify Omise webhook signature
     */
    protected function verifyOmiseSignature(Request $request): bool
    {
        $signature = $request->header('X-Omise-Signature');
        $body = $request->getContent();
        $secret = config('services.omise.secret_key');

        if (!$signature || !$secret) {
            return false;
        }

        $calculatedSignature = hash_hmac('sha256', $body, $secret);

        return hash_equals($calculatedSignature, $signature);
    }
}
