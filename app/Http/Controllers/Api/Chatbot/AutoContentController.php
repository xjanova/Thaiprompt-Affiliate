<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\ChatbotAutoContentPost;
use App\Services\Chatbot\AutoContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Auto Content Controller
 *
 * จัดการการสร้างและโพสต์คอนเทนต์อัตโนมัติ
 */
class AutoContentController extends Controller
{
    protected AutoContentService $autoContentService;

    public function __construct(AutoContentService $autoContentService)
    {
        $this->autoContentService = $autoContentService;
    }

    /**
     * Get all auto content posts for a bot
     */
    public function index($botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $posts = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Create new auto content post
     */
    public function store(Request $request, $botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $validator = Validator::make($request->all(), [
            'topic' => 'nullable|string',
            'content_prompt' => 'required|string',
            'content_guidelines' => 'nullable|array',
            'target_platforms' => 'required|array|min:1',
            'scheduled_at' => 'nullable|date',
            'frequency' => 'required|in:once,daily,weekly,monthly',
            'post_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $post = ChatbotAutoContentPost::create([
            'bot_profile_id' => $botId,
            'topic' => $request->topic,
            'content_prompt' => $request->content_prompt,
            'content_guidelines' => $request->content_guidelines,
            'target_platforms' => $request->target_platforms,
            'platform_specific_config' => $request->platform_specific_config,
            'scheduled_at' => $request->scheduled_at ?? now(),
            'frequency' => $request->frequency,
            'post_days' => $request->post_days,
            'post_time' => $request->post_time,
            'auto_regenerate' => $request->auto_regenerate ?? false,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างคอนเทนต์สำเร็จ',
            'data' => $post,
        ], 201);
    }

    /**
     * Generate content
     */
    public function generate($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $post = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $result = $this->autoContentService->generateContent($post);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        $post->update([
            'generated_content' => $result['content'],
            'hashtags' => $result['hashtags'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างคอนเทนต์สำเร็จ',
            'data' => $post->fresh(),
        ]);
    }

    /**
     * Schedule post
     */
    public function schedule($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $post = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->findOrFail($id);

        if (empty($post->generated_content)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาสร้างคอนเทนต์ก่อนตั้งเวลาโพสต์',
            ], 422);
        }

        $post->update(['status' => 'scheduled']);

        return response()->json([
            'success' => true,
            'message' => 'ตั้งเวลาโพสต์สำเร็จ',
            'data' => $post,
        ]);
    }

    /**
     * Post now
     */
    public function postNow($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $post = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->findOrFail($id);

        // Generate content if not generated
        if (empty($post->generated_content)) {
            $result = $this->autoContentService->generateContent($post);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 422);
            }

            $post->update([
                'generated_content' => $result['content'],
                'hashtags' => $result['hashtags'],
            ]);
        }

        // Post to platforms
        $post->update(['status' => 'processing']);
        $results = $this->autoContentService->postToPlatforms($post);

        $allSuccess = collect($results)->every(fn($r) => $r['success'] ?? false);

        if ($allSuccess) {
            $post->markAsPosted($results);

            return response()->json([
                'success' => true,
                'message' => 'โพสต์คอนเทนต์สำเร็จ',
                'data' => [
                    'post' => $post,
                    'results' => $results,
                ],
            ]);
        }

        $post->markAsFailed('Some platforms failed to post');

        return response()->json([
            'success' => false,
            'message' => 'โพสต์คอนเทนต์ไม่สำเร็จบางแพลตฟอร์ม',
            'data' => [
                'post' => $post,
                'results' => $results,
            ],
        ], 422);
    }

    /**
     * Update post
     */
    public function update(Request $request, $botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $post = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $post->update($request->only([
            'topic',
            'content_prompt',
            'content_guidelines',
            'generated_content',
            'target_platforms',
            'platform_specific_config',
            'scheduled_at',
            'frequency',
            'post_days',
            'post_time',
            'auto_regenerate',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตคอนเทนต์สำเร็จ',
            'data' => $post,
        ]);
    }

    /**
     * Delete post
     */
    public function destroy($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $post = ChatbotAutoContentPost::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบคอนเทนต์สำเร็จ',
        ]);
    }
}
