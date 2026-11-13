<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\FreshSocialOAuth;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

class SocialAccessTokenController extends AccessTokenController
{
    public function issueToken(ServerRequestInterface $request)
    {
        $body = (array) $request->getParsedBody();
        $hasSocial = array_key_exists('social_provider', $body) || array_key_exists('social_nonce', $body);
        if (($body['grant_type'] ?? null) !== 'authorization_code') {
            return $hasSocial ? $this->invalidGrant() : parent::issueToken($request);
        }
        $bridge = app(FreshSocialOAuth::class);
        $code = $body['code'] ?? null;
        $payload = is_string($code) ? $bridge->decodeCode($code) : null;
        if (! $payload) {
            return $hasSocial ? $this->invalidGrant() : parent::issueToken($request);
        }

        // ล็อกแถวเดียวกับที่ Passport revoke เพื่อปิดการแลก social code ซ้ำพร้อมกัน
        return DB::transaction(function () use ($request, $body, $hasSocial, $bridge, $code, $payload) {
            $row = DB::table('oauth_auth_codes')->where('id', $payload['auth_code_id'])->lockForUpdate()->first();
            if (! $row || ! ($row->social_provider ?? null)) {
                return $hasSocial ? $this->invalidGrant() : parent::issueToken($request);
            }

            if ($row->revoked || ! is_string($body['social_provider'] ?? null)
                || ! is_string($body['social_nonce'] ?? null)
                || ! hash_equals($row->social_provider, $body['social_provider'])
                || ! hash_equals($row->social_nonce_hash, hash('sha256', $body['social_nonce']))
                || ! hash_equals($row->social_code_hash, hash('sha256', $code))
                || (string) $row->user_id !== (string) ($payload['user_id'] ?? '')
                || (string) $row->client_id !== (string) ($payload['client_id'] ?? '')
                || ! $bridge->enabled($row->social_provider)) {
                return $this->invalidGrant();
            }

            $response = parent::issueToken($request);
            if ($response->getStatusCode() === 200) {
                $json = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
                $response->setContent(json_encode($json + [
                    'social_provider' => $row->social_provider,
                    'social_nonce' => $body['social_nonce'],
                ], JSON_THROW_ON_ERROR));
            }

            return $response;
        });
    }

    private function invalidGrant()
    {
        return response()->json(['error' => 'invalid_grant', 'error_description' => 'การยืนยันตัวตนไม่ถูกต้องหรือหมดอายุ'], 400)
            ->header('Cache-Control', 'no-store')->header('Pragma', 'no-cache');
    }
}
