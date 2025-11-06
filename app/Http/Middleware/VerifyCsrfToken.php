<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/webhook/line',        // LINE OA webhook endpoint
        'webhook/line',
        'webhook/*',            // All webhook endpoints (payment gateways)
        '/api/webhook/*',       // Payment gateway webhooks
        'api/webhook/*',
    ];
}
