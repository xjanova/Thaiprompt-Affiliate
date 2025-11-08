<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinExchangeRequest;
use App\Models\VideoChannel;
use App\Models\VideoContent;
use App\Models\VideoQuest;
use App\Models\VideoLevel;
use App\Models\CoinExchangeRate;
use App\Models\UserVideoLevel;
use App\Models\VideoCoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoRewardAdminController extends Controller
{
    /**
     * Dashboard statistics
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => UserVideoLevel::count(),
            'total_videos' => VideoContent::count(),
            'total_channels' => VideoChannel::count(),
            'total_quests' => VideoQuest::count(),
            'total_coins_distributed' => VideoCoin::sum('lifetime_earned'),
            'total_coins_balance' => VideoCoin::sum('balance'),
            'pending_exchanges' => CoinExchangeRequest::where('status', 'pending')->count(),
            'total_video_views' => VideoContent::sum('view_count'),
            'total_completions' => VideoContent::sum('completion_count'),
            'users_by_level' => UserVideoLevel::with('currentLevel')
                ->select('current_level_id', DB::raw('count(*) as count'))
                ->groupBy('current_level_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Manage coin exchange requests
     */
    public function exchangeRequests(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = CoinExchangeRequest::with(['user', 'exchangeRate'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Approve exchange request
     */
    public function approveExchange(Request $request, $requestId)
    {
        $exchangeRequest = CoinExchangeRequest::findOrFail($requestId);

        if ($exchangeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is not pending',
            ], 400);
        }

        try {
            $exchangeRequest->approve($request->user()->id, $request->input('note'));

            return response()->json([
                'success' => true,
                'message' => 'Exchange request approved',
                'data' => $exchangeRequest->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject exchange request
     */
    public function rejectExchange(Request $request, $requestId)
    {
        $exchangeRequest = CoinExchangeRequest::findOrFail($requestId);

        if ($exchangeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is not pending',
            ], 400);
        }

        $exchangeRequest->reject($request->user()->id, $request->input('note', 'Rejected by admin'));

        return response()->json([
            'success' => true,
            'message' => 'Exchange request rejected',
            'data' => $exchangeRequest->fresh(),
        ]);
    }

    /**
     * Get all video channels
     */
    public function channels()
    {
        $channels = VideoChannel::withCount('videos')->orderBy('priority')->get();

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }

    /**
     * Create video channel
     */
    public function storeChannel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'channel_url' => 'nullable|url',
            'base_coins_per_video' => 'numeric|min:0',
            'reward_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $channel = VideoChannel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Channel created',
            'data' => $channel,
        ]);
    }

    /**
     * Update video channel
     */
    public function updateChannel(Request $request, $channelId)
    {
        $channel = VideoChannel::findOrFail($channelId);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'channel_url' => 'nullable|url',
            'base_coins_per_video' => 'numeric|min:0',
            'reward_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $channel->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Channel updated',
            'data' => $channel->fresh(),
        ]);
    }

    /**
     * Get all video contents
     */
    public function videos(Request $request)
    {
        $channelId = $request->input('channel_id');

        $query = VideoContent::with('channel');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $videos = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $videos,
        ]);
    }

    /**
     * Create video content
     */
    public function storeVideo(Request $request)
    {
        $validated = $request->validate([
            'channel_id' => 'nullable|exists:video_channels,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'video_type' => 'required|string',
            'video_id' => 'nullable|string',
            'duration_seconds' => 'required|integer|min:1',
            'required_watch_percentage' => 'integer|min:1|max:100',
            'reward_coins' => 'numeric|min:0',
            'reward_exp' => 'integer|min:0',
            'is_active' => 'boolean',
            'reward_enabled' => 'boolean',
        ]);

        $video = VideoContent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Video created',
            'data' => $video,
        ]);
    }

    /**
     * Update video content
     */
    public function updateVideo(Request $request, $videoId)
    {
        $video = VideoContent::findOrFail($videoId);

        $validated = $request->validate([
            'channel_id' => 'nullable|exists:video_channels,id',
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'url',
            'duration_seconds' => 'integer|min:1',
            'required_watch_percentage' => 'integer|min:1|max:100',
            'reward_coins' => 'numeric|min:0',
            'reward_exp' => 'integer|min:0',
            'is_active' => 'boolean',
            'reward_enabled' => 'boolean',
        ]);

        $video->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Video updated',
            'data' => $video->fresh(),
        ]);
    }

    /**
     * Get all quests
     */
    public function quests()
    {
        $quests = VideoQuest::with('channel')->orderBy('priority')->get();

        return response()->json([
            'success' => true,
            'data' => $quests,
        ]);
    }

    /**
     * Create quest
     */
    public function storeQuest(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'frequency' => 'required|string',
            'target_value' => 'required|integer|min:1',
            'channel_id' => 'nullable|exists:video_channels,id',
            'reward_coins' => 'numeric|min:0',
            'reward_exp' => 'integer|min:0',
            'reward_money' => 'numeric|min:0',
            'min_level' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $quest = VideoQuest::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Quest created',
            'data' => $quest,
        ]);
    }

    /**
     * Update quest
     */
    public function updateQuest(Request $request, $questId)
    {
        $quest = VideoQuest::findOrFail($questId);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'string',
            'frequency' => 'string',
            'target_value' => 'integer|min:1',
            'reward_coins' => 'numeric|min:0',
            'reward_exp' => 'integer|min:0',
            'reward_money' => 'numeric|min:0',
            'min_level' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $quest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Quest updated',
            'data' => $quest->fresh(),
        ]);
    }

    /**
     * Get exchange rates
     */
    public function exchangeRates()
    {
        $rates = CoinExchangeRate::orderBy('min_coins')->get();

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Update exchange rate
     */
    public function updateExchangeRate(Request $request, $rateId)
    {
        $rate = CoinExchangeRate::findOrFail($rateId);

        $validated = $request->validate([
            'rate' => 'numeric|min:0',
            'min_level' => 'integer|min:1',
            'min_coins' => 'numeric|min:0',
            'max_coins_per_day' => 'nullable|numeric|min:0',
            'max_coins_per_month' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $rate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate updated',
            'data' => $rate->fresh(),
        ]);
    }
}
