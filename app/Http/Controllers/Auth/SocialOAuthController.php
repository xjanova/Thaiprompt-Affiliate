<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\FreshSocialOAuth;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;

class SocialOAuthController extends Controller
{
    use HandlesOAuthErrors;

    public function providers(FreshSocialOAuth $bridge)
    {
        return response()->json(['data' => ['flow' => FreshSocialOAuth::FLOW, 'providers' => $bridge->providers()]])
            ->header('Cache-Control', 'no-store');
    }

    public function start(Request $request, ServerRequestInterface $psrRequest, AuthorizationServer $server, FreshSocialOAuth $bridge)
    {
        $request->validate([
            'provider' => ['required', 'string', 'in:facebook,google'],
            'social_nonce' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]{32,128}\z/'],
            'response_type' => ['required', 'in:code'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'state' => ['required', 'string', 'max:1024'],
            'scope' => ['sometimes', 'string'],
            'code_challenge' => ['sometimes', 'string'],
            'code_challenge_method' => ['sometimes', 'in:S256'],
            'prompt' => ['sometimes', 'in:consent'],
        ]);
        abort_unless($bridge->enabled($request->query('provider')), 422, 'ช่องทางเข้าสู่ระบบนี้ยังไม่เปิดใช้งาน');

        return $this->withErrorHandling(function () use ($request, $psrRequest, $server, $bridge) {
            // ตรวจ client และ redirect_uri ด้วย Passport ก่อนล้าง session หรือเก็บปลายทาง
            $authorization = $server->validateAuthorizationRequest($psrRequest);
            $bridge->begin($request, $authorization, $request->only(FreshSocialOAuth::PARAMETERS));

            // เรียกตัวเริ่มล็อกอินเดิมโดยไม่ผ่าน guest middleware และไม่ส่ง query ข้าม flow
            $provider = $request->query('provider');
            $request->query->replace([]);
            $controller = $provider === 'facebook' ? FacebookLoginController::class : GoogleLoginController::class;
            $response = app($controller)->redirect($request);

            return $bridge->rememberProviderState($request) ? $response : $bridge->deny($request);
        });
    }
}
