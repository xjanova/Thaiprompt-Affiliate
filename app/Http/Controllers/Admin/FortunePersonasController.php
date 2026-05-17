<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 👤 Fortune Personas Controller — RPG Character Sheet view
 *
 * แสดงบุคลิก/นิสัยลูกค้าที่ระบบจดจำผ่าน FortuneCustomerPersona
 * แบบเกม RPG: Level, Rarity, Radar Chart, Stat Bars
 */
class FortunePersonasController extends Controller
{
    /**
     * 📋 รายการ persona ทั้งหมด — RPG card grid
     */
    public function index(Request $request)
    {
        $query = FortuneCustomerPersona::query();

        // ค้นหา (display_name / platform_user_id / topic_tags)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('topic_tags', 'like', "%{$search}%");
            });
        }

        // กรอง platform
        if ($request->filled('platform') && in_array($request->platform, ['facebook', 'line'])) {
            $query->where('platform', $request->platform);
        }

        // กรอง rarity (เนื่องจากเป็น computed field ต้องดึงทั้งหมดมา filter ใน collection)
        $rarityFilter = $request->input('rarity');

        // กรอง topic tag (chip)
        if ($request->filled('tag')) {
            $query->where('topic_tags', 'like', '%' . $request->tag . '%');
        }

        // เรียง: latest active ก่อน
        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'most_observed' => $query->orderByDesc('observation_count'),
            'oldest' => $query->orderBy('last_observed_at'),
            default => $query->orderByDesc('last_observed_at'),
        };

        // Paginate 24 ต่อหน้า (4x6 grid)
        $perPage = 24;

        if ($rarityFilter && in_array($rarityFilter, ['common', 'rare', 'epic', 'legendary'])) {
            // ⚠️ Rarity เป็น computed field (obs_count + completeness*0.5)
            // Completeness max 50 → ใช้ DB pre-filter ได้เฉพาะ legendary (score ≥ 80 → obs ≥ 30)
            // Rarity อื่นต้อง load ทั้งหมดมา filter — กัน OOM ด้วย hard cap 5000 records ล่าสุด
            if ($rarityFilter === 'legendary') {
                $query->where('observation_count', '>=', 25);
            }

            // Safety cap: 5000 records ล่าสุด — ป้องกัน OOM (admin-only)
            $all = (clone $query)->limit(5000)->get();
            $filtered = $all->filter(fn ($p) => $p->getRarity() === $rarityFilter)->values();

            // Manual paginate
            $page = (int) $request->input('page', 1);
            $items = $filtered->forPage($page, $perPage)->values();
            $personas = new LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $personas = $query->paginate($perPage)->withQueryString();
        }

        // 📊 Stats สำหรับ hero header
        $stats = $this->buildOverviewStats();

        // 🏷️ Top tags (สำหรับ chips filter)
        $topTags = $this->extractTopTags(50);

        return view('admin.fortune.personas.index', [
            'personas' => $personas,
            'stats' => $stats,
            'topTags' => $topTags,
            'filters' => [
                'search' => $request->search,
                'platform' => $request->platform,
                'rarity' => $rarityFilter,
                'tag' => $request->tag,
                'sort' => $sort,
            ],
            'pageTitle' => 'บุคลิกลูกค้า (Persona Memory)',
        ]);
    }

    /**
     * 🎴 รายละเอียด persona — RPG Character Sheet
     */
    public function show(int $id)
    {
        $persona = FortuneCustomerPersona::findOrFail($id);

        // 🔗 Link กับ readings ที่เคยทำนาย (ถ้ามี)
        $readings = FortuneReading::where('platform', $persona->platform)
            ->where('facebook_user_id', $persona->platform_user_id)
            ->select(['id', 'reading_type', 'conversation_status', 'amount_paid', 'is_paid', 'created_at'])
            ->latest()
            ->limit(10)
            ->get();

        $readingStats = [
            'total' => $readings->count(),
            'paid' => $readings->where('is_paid', true)->count(),
            'total_spent' => (float) FortuneReading::where('platform', $persona->platform)
                ->where('facebook_user_id', $persona->platform_user_id)
                ->where('is_paid', true)
                ->sum('amount_paid'),
        ];

        return view('admin.fortune.personas.show', [
            'persona' => $persona,
            'radarStats' => $persona->getRadarStats(),
            'statBars' => $persona->getStatBars(),
            'rarity' => $persona->getRarityConfig(),
            'platform' => $persona->getPlatformConfig(),
            'level' => $persona->getLevel(),
            'xpProgress' => $persona->getXpProgress(),
            'completeness' => $persona->getDataCompleteness(),
            'markdownExport' => $persona->toObsidianMarkdown(),
            'readings' => $readings,
            'readingStats' => $readingStats,
            'pageTitle' => 'Persona — ' . ($persona->display_name ?? $persona->platform_user_id),
        ]);
    }

    /**
     * 📥 Export markdown (.md file download)
     */
    public function exportMarkdown(int $id)
    {
        $persona = FortuneCustomerPersona::findOrFail($id);

        $filename = 'persona-' . $persona->platform . '-' . $persona->id . '.md';
        $content = $persona->toObsidianMarkdown();

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 📊 Overview stats สำหรับ hero header
     */
    private function buildOverviewStats(): array
    {
        $total = FortuneCustomerPersona::count();
        $today = FortuneCustomerPersona::whereDate('created_at', today())->count();
        $activeWeek = FortuneCustomerPersona::where('last_observed_at', '>=', now()->subDays(7))->count();
        $totalObs = (int) FortuneCustomerPersona::sum('observation_count');
        $avgObs = $total > 0 ? (int) round($totalObs / $total) : 0;

        // คำนวณ rarity distribution (sample จาก 500 ล่าสุด ถ้า total > 500 เพื่อ perf)
        $sample = $total > 500
            ? FortuneCustomerPersona::latest('last_observed_at')->limit(500)->get()
            : FortuneCustomerPersona::all();

        $rarityDist = [
            'legendary' => 0,
            'epic' => 0,
            'rare' => 0,
            'common' => 0,
        ];
        foreach ($sample as $p) {
            $rarityDist[$p->getRarity()]++;
        }

        // Platform breakdown
        $platforms = FortuneCustomerPersona::select('platform', DB::raw('COUNT(*) as count'))
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        return [
            'total' => $total,
            'today' => $today,
            'active_week' => $activeWeek,
            'avg_observations' => $avgObs,
            'rarity_dist' => $rarityDist,
            'platforms' => $platforms,
        ];
    }

    /**
     * 🏷️ Extract top tags จาก topic_tags field (สำหรับ chips filter)
     */
    private function extractTopTags(int $limit = 50): array
    {
        $allTags = FortuneCustomerPersona::whereNotNull('topic_tags')
            ->where('topic_tags', '!=', '')
            ->limit(1000)
            ->pluck('topic_tags')
            ->toArray();

        $tagCounts = [];
        foreach ($allTags as $tagsStr) {
            $tags = array_map('trim', explode(',', $tagsStr));
            foreach ($tags as $tag) {
                if (! empty($tag)) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);

        return array_slice($tagCounts, 0, $limit, true);
    }
}
