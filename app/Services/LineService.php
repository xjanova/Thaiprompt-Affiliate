<?php

namespace App\Services;

use App\Models\LineOaSetting;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineService
{
    private ?LineOaSetting $settings;
    private const LINE_API_BASE = 'https://api.line.me';
    private const LINE_OAUTH_BASE = 'https://access.line.me/oauth2/v2.1';

    public function __construct()
    {
        $this->settings = LineOaSetting::getActive();
    }

    /**
     * Get LINE Login authorization URL
     */
    public function getAuthorizationUrl(string $state, ?string $redirectUri = null): string
    {
        if (!$this->settings) {
            throw new Exception('LINE OA settings not configured');
        }

        // Use custom redirect_uri from settings if available, otherwise use route
        $redirectUri = $redirectUri ?? $this->settings->redirect_uri ?? route('line.callback');

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->settings->login_channel_id,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'profile openid email',
            'bot_prompt' => 'aggressive', // Prompt to add as friend
        ]);

        return self::LINE_OAUTH_BASE . "/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code, ?string $redirectUri = null): array
    {
        if (!$this->settings) {
            throw new Exception('LINE OA settings not configured');
        }

        // Use custom redirect_uri from settings if available, otherwise use route
        $redirectUri = $redirectUri ?? $this->settings->redirect_uri ?? route('line.callback');

        $response = Http::asForm()->post(self::LINE_OAUTH_BASE . '/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $this->settings->login_channel_id,
            'client_secret' => $this->settings->channel_secret,
        ]);

        if (!$response->successful()) {
            Log::error('LINE token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to exchange LINE authorization code');
        }

        return $response->json();
    }

    /**
     * Get user profile from LINE
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::LINE_API_BASE . '/v2/profile');

        if (!$response->successful()) {
            Log::error('LINE profile fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch LINE user profile');
        }

        return $response->json();
    }

    /**
     * Verify LINE access token
     */
    public function verifyToken(string $accessToken): bool
    {
        $response = Http::get(self::LINE_OAUTH_BASE . '/verify', [
            'access_token' => $accessToken,
        ]);

        return $response->successful();
    }

    /**
     * Send push message to a user
     */
    public function sendPushMessage(string $lineUserId, string $message, array $additionalMessages = []): bool
    {
        if (!$this->settings || !$this->settings->enable_line_messaging) {
            Log::warning('LINE messaging is disabled');
            return false;
        }

        if (empty($this->settings->channel_access_token)) {
            Log::error('LINE channel access token not configured');
            return false;
        }

        $messages = [
            ['type' => 'text', 'text' => $message]
        ];

        // Add additional messages if provided
        foreach ($additionalMessages as $msg) {
            $messages[] = $msg;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->channel_access_token,
            'Content-Type' => 'application/json',
        ])->post('https://api.line.me/v2/bot/message/push', [
            'to' => $lineUserId,
            'messages' => $messages,
        ]);

        if (!$response->successful()) {
            Log::error('LINE push message failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'line_user_id' => $lineUserId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send Flex Message
     */
    public function sendFlexMessage(string $lineUserId, array $flexMessage, ?array $quickReply = null): bool
    {
        if (!$this->settings || !$this->settings->enable_line_messaging) {
            Log::warning('LINE messaging is disabled');
            return false;
        }

        if (empty($this->settings->channel_access_token)) {
            Log::error('LINE channel access token not configured');
            return false;
        }

        $message = $flexMessage;

        // Add quick reply if provided
        if ($quickReply) {
            $message['quickReply'] = $quickReply;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->channel_access_token,
            'Content-Type' => 'application/json',
        ])->post('https://api.line.me/v2/bot/message/push', [
            'to' => $lineUserId,
            'messages' => [$message],
        ]);

        if (!$response->successful()) {
            Log::error('LINE Flex message failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'line_user_id' => $lineUserId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send welcome message to new user
     */
    public function sendWelcomeMessage(User $user): bool
    {
        if (!$user->line_user_id || !$this->settings) {
            return false;
        }

        return $this->sendPushMessage(
            $user->line_user_id,
            $this->settings->welcome_message
        );
    }

    /**
     * Send registration success message
     */
    public function sendRegistrationSuccessMessage(User $user): bool
    {
        if (!$user->line_user_id || !$this->settings) {
            return false;
        }

        $mlmMember = $user->mlmMembers()->first();
        $message = str_replace(
            ['{name}', '{email}', '{referral_code}'],
            [$user->name, $user->email, $mlmMember?->member_code ?? 'N/A'],
            $this->settings->registration_success_message
        );

        return $this->sendPushMessage($user->line_user_id, $message);
    }

    /**
     * Send flex message with user info
     */
    public function sendUserInfoCard(User $user): bool
    {
        if (!$user->line_user_id) {
            return false;
        }

        $flexMessage = [
            'type' => 'flex',
            'altText' => 'ข้อมูลบัญชีของคุณ',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => $user->profile_picture ?? 'https://via.placeholder.com/300x200',
                    'size' => 'full',
                    'aspectRatio' => '20:13',
                    'aspectMode' => 'cover',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'ข้อมูลบัญชี',
                            'weight' => 'bold',
                            'size' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'ชื่อ',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $user->name,
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'อีเมล',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $user->email,
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'รหัสแนะนำ',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $user->mlmMembers()->first()?->member_code ?? 'N/A',
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'เข้าสู่ระบบ',
                                'uri' => route('login'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendPushMessage(
            $user->line_user_id,
            'ข้อมูลบัญชีของคุณ',
            [$flexMessage]
        );
    }

    /**
     * Check if LINE OA is configured
     */
    public function isConfigured(): bool
    {
        return $this->settings
            && !empty($this->settings->login_channel_id)
            && !empty($this->settings->channel_secret)
            && $this->settings->is_active;
    }

    /**
     * Get settings
     */
    public function getSettings(): ?LineOaSetting
    {
        return $this->settings;
    }

    /**
     * Test LINE API connection
     * Returns array with test results
     */
    public function testConnection(): array
    {
        $results = [
            'overall_status' => 'success',
            'tests' => [],
        ];

        // Test 1: Check if settings are configured
        if (!$this->settings) {
            $results['tests']['settings'] = [
                'status' => 'error',
                'message' => 'LINE OA settings not configured',
            ];
            $results['overall_status'] = 'error';
            return $results;
        }

        $results['tests']['settings'] = [
            'status' => 'success',
            'message' => 'LINE OA settings found',
        ];

        // Test 2: Check if required fields are filled
        if (empty($this->settings->login_channel_id) || empty($this->settings->channel_secret)) {
            $results['tests']['credentials'] = [
                'status' => 'error',
                'message' => 'LINE Login Channel ID or Secret is missing',
            ];
            $results['overall_status'] = 'error';
            return $results;
        }

        $results['tests']['credentials'] = [
            'status' => 'success',
            'message' => 'LINE Login Channel ID and Secret are configured',
        ];

        // Test 3: Test messaging API (if access token is configured)
        if (!empty($this->settings->channel_access_token)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->settings->channel_access_token,
                ])->get('https://api.line.me/v2/bot/info');

                if ($response->successful()) {
                    $botInfo = $response->json();
                    $results['tests']['messaging_api'] = [
                        'status' => 'success',
                        'message' => 'Messaging API connection successful',
                        'bot_info' => [
                            'displayName' => $botInfo['displayName'] ?? 'N/A',
                            'userId' => $botInfo['userId'] ?? 'N/A',
                        ],
                    ];
                } else {
                    $results['tests']['messaging_api'] = [
                        'status' => 'warning',
                        'message' => 'Messaging API connection failed: ' . $response->status(),
                        'details' => $response->body(),
                    ];
                    if ($results['overall_status'] === 'success') {
                        $results['overall_status'] = 'warning';
                    }
                }
            } catch (Exception $e) {
                $results['tests']['messaging_api'] = [
                    'status' => 'error',
                    'message' => 'Messaging API test failed: ' . $e->getMessage(),
                ];
                $results['overall_status'] = 'error';
            }
        } else {
            $results['tests']['messaging_api'] = [
                'status' => 'warning',
                'message' => 'Channel Access Token not configured (required for messaging)',
            ];
            if ($results['overall_status'] === 'success') {
                $results['overall_status'] = 'warning';
            }
        }

        // Test 4: Verify LINE Login configuration
        try {
            $authUrl = $this->getAuthorizationUrl('test_state_' . time());
            $results['tests']['line_login'] = [
                'status' => 'success',
                'message' => 'LINE Login configuration is valid',
                'auth_url' => substr($authUrl, 0, 100) . '...',
            ];
        } catch (Exception $e) {
            $results['tests']['line_login'] = [
                'status' => 'error',
                'message' => 'LINE Login test failed: ' . $e->getMessage(),
            ];
            $results['overall_status'] = 'error';
        }

        return $results;
    }
}
