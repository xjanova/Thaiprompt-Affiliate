<?php

namespace App\Services\Auth;

use App\Models\FacebookOAuthSetting;
use App\Models\GoogleOAuthSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * ผูกการยืนยันตัวตนจากผู้ให้บริการครั้งใหม่กับคำขอและ code ของ Passport
 *
 * @example เริ่มจาก /oauth/social/authorize แล้วกลับผ่าน callback เดิม
 *
 * @tip เก็บหลักฐานบนแถว code ในฐานข้อมูลเพื่อไม่ให้ cache eviction ลดระดับเป็น SSO ปกติ
 */
class FreshSocialOAuth
{
    use CryptTrait;

    public const SESSION = 'juntra_social_oauth';

    public const FLOW = 'juntra-social-v1';

    public const PARAMETERS = ['response_type', 'client_id', 'redirect_uri', 'scope', 'state', 'code_challenge', 'code_challenge_method', 'prompt'];

    public function providers(): array
    {
        $providers = [];
        foreach (['facebook' => 'Facebook', 'google' => 'Google'] as $id => $name) {
            if ($this->enabled($id)) {
                $providers[] = compact('id', 'name');
            }
        }

        return $providers;
    }

    public function enabled(string $provider): bool
    {
        try {
            return match ($provider) {
                'facebook' => FacebookOAuthSetting::isConfigured(),
                'google' => GoogleOAuthSetting::isConfigured(),
                default => false,
            };
        } catch (Throwable) {
            return false;
        }
    }

    public function active(Request $request): bool
    {
        return $request->session()->has(self::SESSION);
    }

    public function begin(Request $request, AuthorizationRequest $authorization, array $parameters): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put(self::SESSION, [
            'provider' => $request->query('provider'),
            'nonce' => $request->query('social_nonce'),
            'parameters' => $parameters,
            'authorization' => $this->fingerprint($authorization),
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]);
    }

    public function rememberProviderState(Request $request): bool
    {
        $state = $request->session()->get('state');
        if (! is_string($state) || $state === '') {
            return false;
        }
        $request->session()->put(self::SESSION.'.provider_state', $state);

        return true;
    }

    public function acceptCallback(Request $request, string $provider): bool
    {
        $flow = $request->session()->get(self::SESSION);
        $state = $request->query('state');
        if (! $flow || isset($flow['user_id']) || $flow['expires_at'] < now()->timestamp
            || $flow['provider'] !== $provider || ! $this->enabled($provider)
            || ! is_string($state) || ! isset($flow['provider_state'])
            || ! hash_equals($flow['provider_state'], $state)) {
            return false;
        }
        $request->attributes->set('fresh_social_callback', $provider);

        return true;
    }

    public function complete(Request $request, string $provider, User $user): RedirectResponse
    {
        abort_unless($request->attributes->get('fresh_social_callback') === $provider, 403);
        $flow = $request->session()->get(self::SESSION);
        abort_unless($flow && $flow['provider'] === $provider && $flow['expires_at'] >= now()->timestamp, 403);
        Auth::guard('web')->logout();
        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION.'.user_id', (string) $user->getAuthIdentifier());
        $request->session()->forget(['state', 'url.intended']);

        return redirect()->route('passport.authorizations.authorize', $flow['parameters']);
    }

    public function validAuthorization(Request $request): bool
    {
        $flow = $request->session()->get(self::SESSION);
        if (! $flow || ! isset($flow['user_id']) || $flow['expires_at'] < now()->timestamp
            || ! $this->enabled($flow['provider'])
            || (string) Auth::guard('web')->id() !== $flow['user_id']) {
            return false;
        }

        if ($request->isMethod('GET')) {
            return $request->only(self::PARAMETERS) == $flow['parameters'];
        }

        $authorization = $request->session()->get('authRequest');

        return $authorization instanceof AuthorizationRequest
            && $this->fingerprint($authorization) === $flow['authorization']
            && is_string($request->input('auth_token'))
            && hash_equals((string) $request->session()->get('authToken'), $request->input('auth_token'));
    }

    public function bindCode(Request $request, Response $response): void
    {
        $location = $response->headers->get('Location');
        if (! $location) {
            return;
        }
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $flow = $request->session()->get(self::SESSION);
        if (isset($query['code']) && is_string($query['code'])) {
            $payload = $this->decodeCode($query['code']);
            abort_unless($payload && (string) ($payload['user_id'] ?? '') === $flow['user_id']
                && (string) ($payload['client_id'] ?? '') === $flow['authorization']['client_id'], 403);

            $updated = DB::table('oauth_auth_codes')
                ->where('id', $payload['auth_code_id'])
                ->where('user_id', $flow['user_id'])
                ->where('client_id', $flow['authorization']['client_id'])
                ->where('revoked', false)
                ->update([
                    'social_provider' => $flow['provider'],
                    'social_nonce_hash' => hash('sha256', $flow['nonce']),
                    'social_code_hash' => hash('sha256', $query['code']),
                ]);
            abort_unless($updated === 1, 403);
        }
        $this->forget($request);
    }

    public function decodeCode(string $code): ?array
    {
        try {
            $this->setEncryptionKey(app('encrypter')->getKey());
            $payload = json_decode($this->decrypt($code), true, 512, JSON_THROW_ON_ERROR);

            return is_array($payload) && isset($payload['auth_code_id']) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function deny(Request $request): RedirectResponse
    {
        $flow = $request->session()->get(self::SESSION);
        $this->forget($request);
        abort_unless($flow && isset($flow['authorization']['redirect_uri']), 403);
        $uri = $flow['authorization']['redirect_uri'];
        $query = http_build_query(['error' => 'access_denied', 'state' => $flow['authorization']['state']]);

        return redirect()->away($uri.(str_contains($uri, '?') ? '&' : '?').$query);
    }

    private function forget(Request $request): void
    {
        $request->session()->forget([self::SESSION, 'authRequest', 'authToken', 'state', 'url.intended']);
    }

    private function fingerprint(AuthorizationRequest $authorization): array
    {
        return [
            'client_id' => (string) $authorization->getClient()->getIdentifier(),
            'redirect_uri' => $authorization->getRedirectUri(),
            'state' => $authorization->getState(),
            'scopes' => array_map(fn ($scope) => $scope->getIdentifier(), $authorization->getScopes()),
            'code_challenge' => $authorization->getCodeChallenge(),
            'code_challenge_method' => $authorization->getCodeChallengeMethod(),
        ];
    }
}
