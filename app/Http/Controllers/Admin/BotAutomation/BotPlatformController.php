<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotPlatformConnection;
use Illuminate\Http\Request;

class BotPlatformController extends Controller
{
    /**
     * Display a listing of platform connections
     */
    public function index()
    {
        $connections = BotPlatformConnection::with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $availablePlatforms = [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook',
                'color' => '#1877f2',
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
            ],
            'twitter' => [
                'name' => 'Twitter (X)',
                'icon' => 'fab fa-twitter',
                'color' => '#1DA1F2',
            ],
            'line' => [
                'name' => 'LINE',
                'icon' => 'fab fa-line',
                'color' => '#00B900',
            ],
            'telegram' => [
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram',
                'color' => '#0088cc',
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25D366',
            ],
        ];

        return view('admin.bot-automation.platforms.index', compact('connections', 'availablePlatforms'));
    }

    /**
     * Show the form for connecting a platform
     */
    public function connect(string $platform)
    {
        return view('admin.bot-automation.platforms.connect', compact('platform'));
    }

    /**
     * แสดงรายละเอียดและการตั้งค่าของ platform connection
     *
     * @param BotPlatformConnection $connection
     * @return \Illuminate\View\View
     */
    public function show(BotPlatformConnection $connection)
    {
        $connection->load(['user']);

        return view('admin.bot-automation.platforms.show', compact('connection'));
    }

    /**
     * แสดงฟอร์มแก้ไขการตั้งค่า platform connection
     *
     * @param BotPlatformConnection $connection
     * @return \Illuminate\View\View
     */
    public function edit(BotPlatformConnection $connection)
    {
        return view('admin.bot-automation.platforms.edit', compact('connection'));
    }

    /**
     * อัพเดทการตั้งค่า platform connection
     *
     * @param Request $request
     * @param BotPlatformConnection $connection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BotPlatformConnection $connection)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'page_id' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $connection->update($validated);

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'อัพเดทการตั้งค่าแพลตฟอร์มสำเร็จ');
    }

    /**
     * Store a newly created platform connection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,twitter,line,telegram,whatsapp',
            'account_name' => 'required|string|max:255',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'page_id' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['expires_at'] = now()->addDays(60); // Default 60 days

        BotPlatformConnection::create($validated);

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'Platform connected successfully');
    }

    /**
     * Remove the specified platform connection
     */
    public function destroy(BotPlatformConnection $connection)
    {
        $connection->delete();

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'Platform disconnected successfully');
    }

    /**
     * รีเฟรช token สำหรับแต่ละ platform
     *
     * @param BotPlatformConnection $connection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshToken(BotPlatformConnection $connection)
    {
        try {
            // เรียก method รีเฟรช token ตาม platform
            $result = match ($connection->platform) {
                'facebook', 'instagram' => $this->refreshFacebookToken($connection),
                'line' => $this->refreshLineToken($connection),
                'twitter' => $this->refreshTwitterToken($connection),
                'telegram' => $this->refreshTelegramToken($connection),
                'whatsapp' => $this->refreshWhatsAppToken($connection),
                default => ['success' => false, 'message' => 'Platform not supported'],
            };

            if ($result['success']) {
                // อัพเดทข้อมูล connection
                $connection->update([
                    'access_token' => $result['access_token'] ?? $connection->access_token,
                    'refresh_token' => $result['refresh_token'] ?? $connection->refresh_token,
                    'expires_at' => $result['expires_at'] ?? now()->addDays(60),
                    'last_synced_at' => now(),
                ]);

                return back()->with('success', 'รีเฟรช token สำเร็จ');
            }

            return back()->with('error', $result['message'] ?? 'รีเฟรช token ล้มเหลว');
        } catch (\Exception $e) {
            logger()->error('Token refresh failed', [
                'platform' => $connection->platform,
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * รีเฟรช Facebook/Instagram token
     *
     * @param BotPlatformConnection $connection
     * @return array
     */
    protected function refreshFacebookToken(BotPlatformConnection $connection): array
    {
        // ตรวจสอบว่ามี refresh token หรือไม่
        if (empty($connection->refresh_token)) {
            return ['success' => false, 'message' => 'ไม่พบ refresh token'];
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'form_params' => [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => config('services.facebook.client_id'),
                    'client_secret' => config('services.facebook.client_secret'),
                    'fb_exchange_token' => $connection->access_token,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 5184000), // 60 days default
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Facebook API error: ' . $e->getMessage()];
        }
    }

    /**
     * รีเฟรช LINE token
     *
     * @param BotPlatformConnection $connection
     * @return array
     */
    protected function refreshLineToken(BotPlatformConnection $connection): array
    {
        // ตรวจสอบว่ามี refresh token หรือไม่
        if (empty($connection->refresh_token)) {
            return ['success' => false, 'message' => 'ไม่พบ refresh token'];
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.line.me/oauth2/v2.1/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $connection->refresh_token,
                    'client_id' => config('services.line.client_id'),
                    'client_secret' => config('services.line.client_secret'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $connection->refresh_token,
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 2592000), // 30 days default
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'LINE API error: ' . $e->getMessage()];
        }
    }

    /**
     * รีเฟรช Twitter/X token
     *
     * @param BotPlatformConnection $connection
     * @return array
     */
    protected function refreshTwitterToken(BotPlatformConnection $connection): array
    {
        // ตรวจสอบว่ามี refresh token หรือไม่
        if (empty($connection->refresh_token)) {
            return ['success' => false, 'message' => 'ไม่พบ refresh token'];
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.twitter.com/2/oauth2/token', [
                'auth' => [
                    config('services.twitter.client_id'),
                    config('services.twitter.client_secret'),
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $connection->refresh_token,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $connection->refresh_token,
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 7200), // 2 hours default
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Twitter API error: ' . $e->getMessage()];
        }
    }

    /**
     * รีเฟรช Telegram token
     *
     * @param BotPlatformConnection $connection
     * @return array
     */
    protected function refreshTelegramToken(BotPlatformConnection $connection): array
    {
        // Telegram Bot tokens ไม่หมดอายุ - เพียงแค่ตรวจสอบว่า token ยังใช้งานได้
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://api.telegram.org/bot{$connection->access_token}/getMe");

            $data = json_decode($response->getBody(), true);

            if ($data['ok'] ?? false) {
                return [
                    'success' => true,
                    'expires_at' => now()->addYears(10), // Telegram tokens don't expire
                ];
            }

            return ['success' => false, 'message' => 'Token ไม่ถูกต้อง'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Telegram API error: ' . $e->getMessage()];
        }
    }

    /**
     * รีเฟรช WhatsApp Business token
     *
     * @param BotPlatformConnection $connection
     * @return array
     */
    protected function refreshWhatsAppToken(BotPlatformConnection $connection): array
    {
        // WhatsApp Business API uses Meta's token system (same as Facebook)
        return $this->refreshFacebookToken($connection);
    }

    /**
     * Toggle platform connection active status
     */
    public function toggle(BotPlatformConnection $connection)
    {
        $connection->update([
            'is_active' => !$connection->is_active,
        ]);

        $status = $connection->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Platform connection {$status} successfully");
    }
}
