<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageSentiment;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sentiment Analysis Controller
 *
 * จัดการการวิเคราะห์ความรู้สึกและอารมณ์จากข้อความผู้ใช้
 */
class SentimentAnalysisController extends Controller
{
    public function __construct(
        private SentimentAnalysisService $sentimentService
    ) {}

    /**
     * แสดง sentiment analysis dashboard
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $days = $request->get('days', 30);
        $sentiment = $request->get('sentiment'); // positive, negative, neutral
        $type = $request->get('type'); // all, complaints, urgent

        $query = MessageSentiment::where('created_at', '>=', now()->subDays($days));

        if ($sentiment) {
            $query = $query->where('sentiment', $sentiment);
        }

        if ($type === 'complaints') {
            $query = $query->complaints();
        } elseif ($type === 'urgent') {
            $query = $query->urgent();
        }

        $sentiments = $query->latest()->paginate(20);
        $statistics = $this->sentimentService->getSentimentStatistics($days);
        $painPoints = $this->sentimentService->getPainPointsDistribution($days);
        $trend = $this->sentimentService->getSentimentsTrend($days);
        $recommendations = $this->sentimentService->getRecommendations($days);
        $topComplaints = $this->sentimentService->getTopComplaints(5, $days);

        return view('admin.line-bot.keywords.sentiment-analysis.index', [
            'sentiments' => $sentiments,
            'statistics' => $statistics,
            'painPoints' => $painPoints,
            'trend' => $trend,
            'recommendations' => $recommendations,
            'topComplaints' => $topComplaints,
            'days' => $days,
            'filter_sentiment' => $sentiment,
            'filter_type' => $type,
        ]);
    }

    /**
     * แสดงรายละเอียด sentiment
     *
     * @param MessageSentiment $sentiment
     * @return View
     */
    public function show(MessageSentiment $sentiment): View
    {
        return view('admin.line-bot.keywords.sentiment-analysis.show', [
            'sentiment' => $sentiment,
        ]);
    }

    /**
     * ได้ sentiment data เป็น JSON
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listJson(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $sentiment = $request->get('sentiment');
        $type = $request->get('type');

        $query = MessageSentiment::where('created_at', '>=', now()->subDays($days));

        if ($sentiment) {
            $query = $query->where('sentiment', $sentiment);
        }

        if ($type === 'complaints') {
            $query = $query->complaints();
        } elseif ($type === 'urgent') {
            $query = $query->urgent();
        }

        $sentiments = $query->latest()->limit(100)->get();

        return response()->json([
            'success' => true,
            'count' => $sentiments->count(),
            'data' => $sentiments->map(function ($s) {
                return [
                    'id' => $s->id,
                    'sentiment' => $s->sentiment,
                    'message' => $s->user_message,
                    'score' => $s->sentiment_score,
                    'confidence' => $s->confidence,
                    'is_complaint' => $s->is_complaint,
                    'is_urgent' => $s->is_urgent,
                    'issues' => $s->detected_issues,
                ];
            }),
        ]);
    }

    /**
     * ได้ statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $statistics = $this->sentimentService->getSentimentStatistics($days);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    /**
     * ได้ sentiment trend data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trendData(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $trend = $this->sentimentService->getSentimentsTrend($days);

        // Format for chart
        $dates = [];
        $positive = [];
        $negative = [];
        $neutral = [];

        foreach ($trend as $item) {
            if (!in_array($item->date, $dates)) {
                $dates[] = $item->date;
            }

            if ($item->sentiment === 'positive') {
                $positive[$item->date] = $item->count;
            } elseif ($item->sentiment === 'negative') {
                $negative[$item->date] = $item->count;
            } else {
                $neutral[$item->date] = $item->count;
            }
        }

        return response()->json([
            'success' => true,
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Positive',
                    'data' => array_map(fn ($date) => $positive[$date] ?? 0, $dates),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Negative',
                    'data' => array_map(fn ($date) => $negative[$date] ?? 0, $dates),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'Neutral',
                    'data' => array_map(fn ($date) => $neutral[$date] ?? 0, $dates),
                    'borderColor' => '#9CA3AF',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                ],
            ],
        ]);
    }

    /**
     * ได้ pain points distribution
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function painPointsData(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $painPoints = $this->sentimentService->getPainPointsDistribution($days);

        return response()->json([
            'success' => true,
            'labels' => $painPoints->pluck('issue'),
            'data' => $painPoints->pluck('count'),
        ]);
    }

    /**
     * ได้ emotion distribution
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function emotionData(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $emotions = MessageSentiment::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                ROUND(AVG(joy_score), 2) as joy,
                ROUND(AVG(anger_score), 2) as anger,
                ROUND(AVG(sadness_score), 2) as sadness,
                ROUND(AVG(fear_score), 2) as fear,
                ROUND(AVG(surprise_score), 2) as surprise
            ')
            ->first();

        return response()->json([
            'success' => true,
            'emotions' => [
                'joy' => $emotions->joy,
                'anger' => $emotions->anger,
                'sadness' => $emotions->sadness,
                'fear' => $emotions->fear,
                'surprise' => $emotions->surprise,
            ],
        ]);
    }

    /**
     * ได้ recommendations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendations(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $recommendations = $this->sentimentService->getRecommendations($days);

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * ได้ top complaints
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topComplaints(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        $days = $request->get('days', 30);
        $complaints = $this->sentimentService->getTopComplaints($limit, $days);

        return response()->json([
            'success' => true,
            'count' => $complaints->count(),
            'data' => $complaints->map(function ($c) {
                return [
                    'id' => $c->id,
                    'message' => $c->user_message,
                    'sentiment' => $c->sentiment,
                    'issues' => $c->detected_issues,
                    'is_urgent' => $c->is_urgent,
                    'created_at' => $c->created_at,
                ];
            }),
        ]);
    }

    /**
     * ได้ urgent issues
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function urgentIssues(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $urgent = $this->sentimentService->getUrgentIssues($limit);

        return response()->json([
            'success' => true,
            'count' => $urgent->count(),
            'data' => $urgent->map(function ($u) {
                return [
                    'id' => $u->id,
                    'message' => $u->user_message,
                    'sentiment' => $u->sentiment,
                    'issues' => $u->detected_issues,
                    'user_id' => $u->line_user_id,
                    'created_at' => $u->created_at,
                ];
            }),
        ]);
    }

    /**
     * Export sentiment report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $sentiment = $request->get('sentiment');
        $type = $request->get('type');

        $query = MessageSentiment::where('created_at', '>=', now()->subDays($days));

        if ($sentiment) {
            $query = $query->where('sentiment', $sentiment);
        }

        if ($type === 'complaints') {
            $query = $query->complaints();
        } elseif ($type === 'urgent') {
            $query = $query->urgent();
        }

        $sentiments = $query->latest()->get();
        $statistics = $this->sentimentService->getSentimentStatistics($days);
        $painPoints = $this->sentimentService->getPainPointsDistribution($days);
        $recommendations = $this->sentimentService->getRecommendations($days);

        return response()->json([
            'success' => true,
            'report' => [
                'generated_at' => now()->toIso8601String(),
                'period_days' => $days,
                'statistics' => $statistics,
                'pain_points' => $painPoints,
                'recommendations' => $recommendations,
                'total_sentiments_analyzed' => $sentiments->count(),
                'sentiments' => $sentiments->map(function ($s) {
                    return [
                        'message' => $s->user_message,
                        'sentiment' => $s->sentiment,
                        'score' => $s->sentiment_score,
                        'confidence' => $s->confidence,
                        'issues' => $s->detected_issues,
                        'is_complaint' => $s->is_complaint,
                        'is_urgent' => $s->is_urgent,
                        'created_at' => $s->created_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * ลบ sentiment record
     *
     * @param MessageSentiment $sentiment
     * @return JsonResponse
     */
    public function destroy(MessageSentiment $sentiment): JsonResponse
    {
        $sentiment->delete();

        activity()
            ->performedOn($sentiment)
            ->log('ลบ sentiment record');

        return response()->json([
            'success' => true,
            'message' => 'ลบ sentiment record สำเร็จ',
        ]);
    }
}
