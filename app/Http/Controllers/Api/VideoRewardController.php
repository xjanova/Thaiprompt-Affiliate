<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoChannel;
use App\Models\VideoContent;
use App\Models\UserVideoLevel;
use App\Models\VideoCoin;
use App\Models\VideoLevel;
use App\Models\UserDailyStreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoRewardController extends Controller
{
    /**
     * Get user's video reward dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Get or create user level
        $userLevel = UserVideoLevel::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_level_id' => VideoLevel::where('level', 1)->first()->id,
                'current_exp' => 0,
                'total_exp' => 0,
            ]
        );

        // Get or create coin balance
        $coinBalance = VideoCoin::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Get daily streak
        $dailyStreak = UserDailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            ['current_streak' => 0]
        );

        // Get available videos
        $availableVideos = VideoContent::with('channel')
            ->active()
            ->rewardEnabled()
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('video_id')
                    ->from('user_video_watches')
                    ->where('user_id', $user->id)
                    ->where('reward_claimed', true);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get user's recent watches
        $recentWatches = $user->videoWatches()
            ->with('video')
            ->orderBy('last_watched_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user_level' => [
                    'level' => $userLevel->currentLevel->level,
                    'name' => $userLevel->currentLevel->name,
                    'icon' => $userLevel->currentLevel->icon,
                    'current_exp' => $userLevel->current_exp,
                    'next_level_exp' => $userLevel->currentLevel->next_level?->required_exp ?? null,
                    'progress_percentage' => $userLevel->progress_to_next_level,
                    'total_videos_watched' => $userLevel->videos_watched,
                    'total_quests_completed' => $userLevel->quests_completed,
                ],
                'coins' => [
                    'balance' => (float) $coinBalance->balance,
                    'lifetime_earned' => (float) $coinBalance->lifetime_earned,
                    'lifetime_spent' => (float) $coinBalance->lifetime_spent,
                    'formatted_balance' => $coinBalance->formatted_balance,
                ],
                'daily_streak' => [
                    'current_streak' => $dailyStreak->current_streak,
                    'longest_streak' => $dailyStreak->longest_streak,
                    'is_active_today' => $dailyStreak->last_activity_date == now()->toDateString(),
                ],
                'available_videos' => $availableVideos,
                'recent_watches' => $recentWatches,
            ],
        ]);
    }

    /**
     * Get all video channels
     */
    public function channels(Request $request)
    {
        $channels = VideoChannel::active()
            ->rewardEnabled()
            ->withCount('activeVideos')
            ->orderBy('priority')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }

    /**
     * Get videos by channel
     */
    public function channelVideos(Request $request, $channelId)
    {
        $channel = VideoChannel::findOrFail($channelId);
        $user = $request->user();

        $videos = VideoContent::where('channel_id', $channelId)
            ->active()
            ->rewardEnabled()
            ->with(['userWatches' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel,
                'videos' => $videos,
            ],
        ]);
    }

    /**
     * Get video details
     */
    public function videoDetails(Request $request, $videoId)
    {
        $user = $request->user();
        $video = VideoContent::with('channel')->findOrFail($videoId);

        // Get user's progress
        $userProgress = $video->getWatchProgress($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'video' => $video,
                'user_progress' => $userProgress,
                'required_watch_time' => $video->required_watch_time,
                'can_claim_reward' => $userProgress && $userProgress->completed && !$userProgress->reward_claimed,
            ],
        ]);
    }

    /**
     * Get leaderboard
     */
    public function leaderboard(Request $request)
    {
        $type = $request->input('type', 'level'); // level, coins, videos

        $query = UserVideoLevel::with(['user', 'currentLevel']);

        switch ($type) {
            case 'coins':
                $query->join('video_coins', 'user_video_levels.user_id', '=', 'video_coins.user_id')
                    ->orderBy('video_coins.balance', 'desc')
                    ->select('user_video_levels.*');
                break;
            case 'videos':
                $query->orderBy('videos_watched', 'desc');
                break;
            case 'level':
            default:
                $query->join('video_levels', 'user_video_levels.current_level_id', '=', 'video_levels.id')
                    ->orderBy('video_levels.level', 'desc')
                    ->orderBy('user_video_levels.current_exp', 'desc')
                    ->select('user_video_levels.*');
                break;
        }

        $leaderboard = $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_videos_watched' => $user->videoWatches()->where('completed', true)->count(),
            'total_watch_time_hours' => round($user->videoWatches()->sum('watch_time_seconds') / 3600, 2),
            'total_coins_earned' => (float) VideoCoin::where('user_id', $user->id)->value('lifetime_earned') ?? 0,
            'total_quests_completed' => $user->questProgress()->where('completed', true)->count(),
            'total_referrals' => $user->videoReferrals()->count(),
            'daily_streak' => UserDailyStreak::where('user_id', $user->id)->first()?->current_streak ?? 0,
            'videos_by_channel' => DB::table('user_video_watches')
                ->join('video_contents', 'user_video_watches.video_id', '=', 'video_contents.id')
                ->join('video_channels', 'video_contents.channel_id', '=', 'video_channels.id')
                ->where('user_video_watches.user_id', $user->id)
                ->where('user_video_watches.completed', true)
                ->groupBy('video_channels.id', 'video_channels.name')
                ->select('video_channels.name', DB::raw('count(*) as count'))
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
