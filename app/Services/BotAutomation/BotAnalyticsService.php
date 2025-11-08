<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotAutomation;
use App\Models\BotAutomation\BotScheduledPost;
use App\Models\BotAutomation\BotSocialPlatform;
use App\Models\BotAutomation\BotMarketplaceListing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BotAnalyticsService
{
    /**
     * Get overview analytics
     */
    public function getOverview(int $userId = null): array
    {
        $query = BotAutomation::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalAutomations = $query->count();
        $activeAutomations = $query->where('is_active', true)->count();
        $totalExecutions = $query->sum('total_executions');
        $successfulExecutions = $query->sum('successful_executions');
        $successRate = $totalExecutions > 0 ? ($successfulExecutions / $totalExecutions) * 100 : 0;

        // Posts statistics
        $postsQuery = BotScheduledPost::query()
            ->whereHas('automation', function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            });

        $totalPosts = $postsQuery->count();
        $publishedPosts = $postsQuery->where('status', 'published')->count();
        $pendingPosts = $postsQuery->where('status', 'pending')->count();
        $failedPosts = $postsQuery->where('status', 'failed')->count();

        // Engagement statistics
        $totalLikes = $postsQuery->sum('likes_count');
        $totalComments = $postsQuery->sum('comments_count');
        $totalShares = $postsQuery->sum('shares_count');
        $totalViews = $postsQuery->sum('views_count');

        return [
            'automations' => [
                'total' => $totalAutomations,
                'active' => $activeAutomations,
                'inactive' => $totalAutomations - $activeAutomations,
            ],
            'executions' => [
                'total' => $totalExecutions,
                'successful' => $successfulExecutions,
                'failed' => $totalExecutions - $successfulExecutions,
                'success_rate' => round($successRate, 2),
            ],
            'posts' => [
                'total' => $totalPosts,
                'published' => $publishedPosts,
                'pending' => $pendingPosts,
                'failed' => $failedPosts,
            ],
            'engagement' => [
                'likes' => $totalLikes,
                'comments' => $totalComments,
                'shares' => $totalShares,
                'views' => $totalViews,
                'engagement_rate' => $totalViews > 0 ? (($totalLikes + $totalComments + $totalShares) / $totalViews) * 100 : 0,
            ],
        ];
    }

    /**
     * Get posts analytics
     */
    public function getPostsAnalytics(int $userId = null, array $filters = []): array
    {
        $query = BotScheduledPost::query()
            ->with(['automation', 'platformConnection.platform'])
            ->whereHas('automation', function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            });

        // Apply filters
        if (!empty($filters['platform'])) {
            $query->whereHas('platformConnection', function ($q) use ($filters) {
                $q->where('platform_id', $filters['platform']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('scheduled_for', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('scheduled_for', '<=', $filters['date_to']);
        }

        $posts = $query->orderBy('scheduled_for', 'desc')->paginate(20);

        // Calculate totals
        $totals = [
            'likes' => $query->sum('likes_count'),
            'comments' => $query->sum('comments_count'),
            'shares' => $query->sum('shares_count'),
            'views' => $query->sum('views_count'),
        ];

        return [
            'posts' => $posts,
            'totals' => $totals,
        ];
    }

    /**
     * Get platform analytics
     */
    public function getPlatformAnalytics(int $userId = null): array
    {
        $platforms = BotSocialPlatform::active()->get();
        $analytics = [];

        foreach ($platforms as $platform) {
            $query = BotScheduledPost::query()
                ->whereHas('platformConnection', function ($q) use ($platform) {
                    $q->where('platform_id', $platform->id);
                })
                ->whereHas('automation', function ($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                });

            $totalPosts = $query->count();
            $publishedPosts = $query->where('status', 'published')->count();

            $analytics[] = [
                'platform' => [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'code' => $platform->code,
                    'icon' => $platform->icon,
                ],
                'posts' => [
                    'total' => $totalPosts,
                    'published' => $publishedPosts,
                    'success_rate' => $totalPosts > 0 ? ($publishedPosts / $totalPosts) * 100 : 0,
                ],
                'engagement' => [
                    'likes' => $query->sum('likes_count'),
                    'comments' => $query->sum('comments_count'),
                    'shares' => $query->sum('shares_count'),
                    'views' => $query->sum('views_count'),
                ],
            ];
        }

        return $analytics;
    }

    /**
     * Get engagement trends
     */
    public function getEngagementTrends(int $userId = null, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $trends = BotScheduledPost::query()
            ->whereHas('automation', function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            })
            ->where('published_at', '>=', $startDate)
            ->where('status', 'published')
            ->select(
                DB::raw('DATE(published_at) as date'),
                DB::raw('SUM(likes_count) as likes'),
                DB::raw('SUM(comments_count) as comments'),
                DB::raw('SUM(shares_count) as shares'),
                DB::raw('SUM(views_count) as views'),
                DB::raw('COUNT(*) as posts')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $totalEngagement = $item->likes + $item->comments + $item->shares;
                $engagementRate = $item->views > 0 ? ($totalEngagement / $item->views) * 100 : 0;

                return [
                    'date' => $item->date,
                    'likes' => $item->likes,
                    'comments' => $item->comments,
                    'shares' => $item->shares,
                    'views' => $item->views,
                    'posts' => $item->posts,
                    'engagement_rate' => round($engagementRate, 2),
                ];
            });

        return $trends->toArray();
    }

    /**
     * Get revenue analytics (marketplace)
     */
    public function getRevenueAnalytics(int $userId = null): array
    {
        $query = BotMarketplaceListing::query();

        if ($userId) {
            $query->whereHas('automation', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $totalListings = $query->count();
        $activeListings = $query->published()->count();
        $totalRevenue = $query->sum('total_revenue');
        $totalPurchases = $query->sum('total_purchases');
        $totalRentals = $query->sum('total_rentals');
        $avgRevenue = $query->avg('total_revenue');

        // Top performing bots
        $topBots = $query->published()
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($listing) => [
                'id' => $listing->id,
                'title' => $listing->title,
                'revenue' => $listing->total_revenue,
                'purchases' => $listing->total_purchases,
                'rentals' => $listing->total_rentals,
                'rating' => $listing->average_rating,
            ]);

        return [
            'listings' => [
                'total' => $totalListings,
                'active' => $activeListings,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'average' => round($avgRevenue ?? 0, 2),
            ],
            'sales' => [
                'purchases' => $totalPurchases,
                'rentals' => $totalRentals,
                'total' => $totalPurchases + $totalRentals,
            ],
            'top_bots' => $topBots,
        ];
    }

    /**
     * Get automation type distribution
     */
    public function getAutomationTypeDistribution(int $userId = null): array
    {
        $query = BotAutomation::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $distribution = $query->select('automation_type', DB::raw('COUNT(*) as count'))
            ->groupBy('automation_type')
            ->get()
            ->map(fn($item) => [
                'type' => $item->automation_type,
                'count' => $item->count,
            ]);

        return $distribution->toArray();
    }

    /**
     * Export analytics to CSV
     */
    public function exportToCSV(int $userId = null, array $filters = []): string
    {
        $analytics = $this->getPostsAnalytics($userId, $filters);

        $csv = "Date,Platform,Content,Status,Likes,Comments,Shares,Views,Engagement Rate\n";

        foreach ($analytics['posts'] as $post) {
            $engagementRate = $post->views_count > 0
                ? (($post->likes_count + $post->comments_count + $post->shares_count) / $post->views_count) * 100
                : 0;

            $csv .= sprintf(
                "%s,%s,\"%s\",%s,%d,%d,%d,%d,%.2f%%\n",
                $post->scheduled_for->format('Y-m-d H:i'),
                $post->platformConnection->platform->name,
                str_replace('"', '""', mb_substr($post->content, 0, 100)),
                $post->status,
                $post->likes_count,
                $post->comments_count,
                $post->shares_count,
                $post->views_count,
                $engagementRate
            );
        }

        return $csv;
    }
}
