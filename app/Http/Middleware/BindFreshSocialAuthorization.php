<?php

namespace App\Http\Middleware;

use App\Services\Auth\FreshSocialOAuth;
use Closure;
use Illuminate\Http\Request;

class BindFreshSocialAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        $bridge = app(FreshSocialOAuth::class);
        if (! $bridge->active($request)) {
            return $next($request);
        }
        if (! $bridge->validAuthorization($request)) {
            return $bridge->deny($request);
        }
        $response = $next($request);
        $bridge->bindCode($request, $response);

        return $response;
    }
}
