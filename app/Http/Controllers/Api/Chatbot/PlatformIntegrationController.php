<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\ChatbotPlatformIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Platform Integration Controller
 *
 * จัดการการเชื่อมต่อกับแพลตฟอร์มต่างๆ
 */
class PlatformIntegrationController extends Controller
{
    /**
     * Get all integrations for a bot
     */
    public function index($botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $integrations = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->latest()
            ->get();

        // Add connection status to each integration
        $integrations->transform(function ($integration) {
            $integration->connection_status = $integration->getConnectionStatus();
            return $integration;
        });

        return response()->json([
            'success' => true,
            'data' => $integrations,
        ]);
    }

    /**
     * Create new platform integration
     */
    public function store(Request $request, $botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $validator = Validator::make($request->all(), [
            'platform_type' => 'required|in:line,facebook,instagram,telegram,discord,whatsapp,twitter,messenger,slack,web_widget,custom_api',
            'platform_name' => 'nullable|string|max:255',
            'line_channel_id' => 'required_if:platform_type,line',
            'line_channel_secret' => 'required_if:platform_type,line',
            'line_channel_access_token' => 'required_if:platform_type,line',
            'fb_page_id' => 'required_if:platform_type,facebook,instagram',
            'access_token' => 'required_if:platform_type,facebook,instagram',
            'telegram_bot_token' => 'required_if:platform_type,telegram',
            'discord_bot_token' => 'required_if:platform_type,discord',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if integration already exists for this platform
        $exists = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->where('platform_type', $request->platform_type)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'มีการเชื่อมต่อกับแพลตฟอร์มนี้อยู่แล้ว',
            ], 422);
        }

        $integration = ChatbotPlatformIntegration::create([
            'bot_profile_id' => $botId,
            'platform_type' => $request->platform_type,
            'platform_name' => $request->platform_name,
            'access_token' => $request->access_token,
            'refresh_token' => $request->refresh_token,
            'webhook_secret' => $request->webhook_secret,
            'platform_credentials' => $request->platform_credentials,
            'line_channel_id' => $request->line_channel_id,
            'line_channel_secret' => $request->line_channel_secret,
            'line_channel_access_token' => $request->line_channel_access_token,
            'fb_page_id' => $request->fb_page_id,
            'fb_app_id' => $request->fb_app_id,
            'fb_app_secret' => $request->fb_app_secret,
            'telegram_bot_token' => $request->telegram_bot_token,
            'telegram_username' => $request->telegram_username,
            'discord_bot_token' => $request->discord_bot_token,
            'discord_client_id' => $request->discord_client_id,
            'auto_reply_enabled' => $request->auto_reply_enabled ?? true,
            'keyword_matching_enabled' => $request->keyword_matching_enabled ?? true,
            'ai_fallback_enabled' => $request->ai_fallback_enabled ?? true,
            'work_in_all_rooms' => $request->work_in_all_rooms ?? true,
            'can_post_content' => $request->can_post_content ?? false,
            'is_active' => true,
        ]);

        // Verify credentials
        $credentialsValid = $integration->verifyCredentials();

        if ($credentialsValid) {
            $integration->markAsConnected();
        }

        return response()->json([
            'success' => true,
            'message' => 'เชื่อมต่อแพลตฟอร์มสำเร็จ',
            'data' => $integration,
        ], 201);
    }

    /**
     * Update integration
     */
    public function update(Request $request, $botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $integration = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $integration->update($request->only([
            'platform_name',
            'access_token',
            'refresh_token',
            'webhook_secret',
            'platform_credentials',
            'enabled_features',
            'platform_settings',
            'auto_reply_enabled',
            'keyword_matching_enabled',
            'ai_fallback_enabled',
            'response_delay_ms',
            'allowed_rooms',
            'blocked_rooms',
            'work_in_all_rooms',
            'can_post_content',
            'admin_rooms',
            'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตการเชื่อมต่อสำเร็จ',
            'data' => $integration,
        ]);
    }

    /**
     * Delete integration
     */
    public function destroy($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $integration = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $integration->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการเชื่อมต่อสำเร็จ',
        ]);
    }

    /**
     * Verify integration
     */
    public function verify($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $integration = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $verified = $integration->verifyCredentials();

        if ($verified) {
            $integration->markAsConnected();

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันการเชื่อมต่อสำเร็จ',
                'data' => $integration,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'ไม่สามารถยืนยันการเชื่อมต่อได้ กรุณาตรวจสอบข้อมูลการเชื่อมต่อ',
        ], 422);
    }

    /**
     * Get webhook URL
     */
    public function getWebhookUrl($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $integration = ChatbotPlatformIntegration::where('bot_profile_id', $botId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'webhook_url' => $integration->getWebhookUrl(),
                'platform_type' => $integration->platform_type,
            ],
        ]);
    }
}
