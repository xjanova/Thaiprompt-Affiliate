<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRentalCloudProvider;
use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalDeployment;
use App\Models\HuggingFaceModelNews;
use App\Models\HuggingFaceTrendingModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Rental Controller
 *
 * จัดการระบบให้เช่า AI Generation บน Cloud GPU
 * รองรับ Hugging Face models และ Cloud providers ต่างๆ
 */
class AiRentalController extends Controller
{
    /**
     * แสดงหน้า Dashboard AI Rental
     *
     * แสดงภาพรวมของระบบ AI Rental พร้อมสถิติและสถานะโครงการ
     *
     * @return View
     */
    public function dashboard(): View
    {
        // ดึงสถิติเบื้องต้น
        $stats = [
            // Cloud Providers
            'total_providers' => AiRentalCloudProvider::count(),
            'free_providers' => AiRentalCloudProvider::freeTier()->count(),
            'active_providers' => AiRentalCloudProvider::active()->count(),

            // Configurations
            'total_configs' => AiRentalCloudConfig::count(),
            'active_configs' => AiRentalCloudConfig::active()->count(),

            // Deployments
            'total_deployments' => AiRentalDeployment::count(),
            'running_deployments' => AiRentalDeployment::running()->count(),
            'total_gpu_hours' => AiRentalDeployment::sum('total_hours'),
            'total_cost' => AiRentalDeployment::sum('total_cost'),

            // Hugging Face
            'trending_models' => HuggingFaceTrendingModel::count(),
            'hot_models' => HuggingFaceTrendingModel::where('trending_score', '>=', 70)->count(),
            'latest_news' => HuggingFaceModelNews::published()->count(),
        ];

        // สถานะการพัฒนาโครงการ (%)
        $project_status = [
            'database' => 100,           // ✅ Migrations & Models เสร็จ 100%
            'menu' => 100,               // ✅ เมนู Admin เสร็จ 100%
            'cloud_providers' => 100,    // ✅ Seeder + UI Dashboard
            'huggingface_integration' => 80, // ✅ Trending Models + News (ยังไม่มี API integration)
            'deployment_system' => 10,   // 🔄 เริ่มออกแบบ (Coming Soon)
            'news_system' => 100,        // ✅ News Feed System เสร็จสมบูรณ์!
            'setup_guide' => 100,        // ✅ Setup Guide พร้อม Cost Calculator!
            'testing' => 0,              // ⏳ ยังไม่เริ่มทำ
        ];

        // คำนวณความสมบูรณ์โดยรวม
        $overall_completion = round(array_sum($project_status) / count($project_status));

        // ข่าวล่าสุด (5 รายการ)
        $latest_news = HuggingFaceModelNews::published()
            ->featured()
            ->latest('published_at')
            ->take(5)
            ->get();

        // Models ที่กำลัง trending (5 อันดับแรก)
        $trending_models = HuggingFaceTrendingModel::trending()
            ->take(5)
            ->get();

        // Cloud providers ที่แนะนำ (ฟรี + ราคาถูก)
        $recommended_providers = AiRentalCloudProvider::active()
            ->recommended()
            ->ordered()
            ->take(6)
            ->get();

        // Deployments ล่าสุด
        $recent_deployments = AiRentalDeployment::with(['user', 'cloudConfig.cloudProvider'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.ai-rental.dashboard', compact(
            'stats',
            'project_status',
            'overall_completion',
            'latest_news',
            'trending_models',
            'recommended_providers',
            'recent_deployments'
        ));
    }

    /**
     * แสดงคู่มือการตั้งค่า Cloud GPU
     *
     * คู่มือแบบ Step-by-step สำหรับการตั้งค่า Cloud ต่างๆ
     *
     * @return View
     */
    public function setupGuide(): View
    {
        // ดึง Cloud providers ทั้งหมด
        $providers = AiRentalCloudProvider::active()
            ->ordered()
            ->get();

        return view('admin.ai-rental.setup-guide', compact('providers'));
    }

    /**
     * แสดงเครื่องคำนวณค่าใช้จ่าย
     *
     * คำนวณค่าใช้จ่ายโดยประมาณสำหรับการใช้ Cloud GPU
     *
     * @return View
     */
    public function costCalculator(): View
    {
        // ดึง Cloud providers พร้อมราคา
        $providers = AiRentalCloudProvider::active()
            ->whereNotNull('min_price_per_hour')
            ->ordered()
            ->get();

        return view('admin.ai-rental.cost-calculator', compact('providers'));
    }

    /**
     * คำนวณค่าใช้จ่าย (API)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateCost(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:ai_rental_cloud_providers,id',
            'hours' => 'required|numeric|min:0.1',
            'gpu_count' => 'required|integer|min:1|max:8',
        ]);

        $provider = AiRentalCloudProvider::findOrFail($validated['provider_id']);

        // ใช้ราคาเฉลี่ย
        $hourlyRate = $provider->average_price ?? $provider->min_price_per_hour ?? 0;

        // คำนวณค่าใช้จ่าย
        $totalCost = $hourlyRate * $validated['hours'] * $validated['gpu_count'];

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => $provider->name,
                'hourly_rate' => $hourlyRate,
                'hours' => $validated['hours'],
                'gpu_count' => $validated['gpu_count'],
                'total_cost' => round($totalCost, 2),
                'currency' => 'USD',
            ],
        ]);
    }

    /**
     * แสดงสถิติโดยรวม (API)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'providers' => [
                'total' => AiRentalCloudProvider::count(),
                'active' => AiRentalCloudProvider::active()->count(),
                'free' => AiRentalCloudProvider::freeTier()->count(),
            ],
            'deployments' => [
                'total' => AiRentalDeployment::count(),
                'running' => AiRentalDeployment::running()->count(),
                'stopped' => AiRentalDeployment::stopped()->count(),
            ],
            'usage' => [
                'total_hours' => AiRentalDeployment::sum('total_hours'),
                'total_cost' => AiRentalDeployment::sum('total_cost'),
                'total_requests' => AiRentalDeployment::sum('total_requests'),
            ],
            'trending' => [
                'models_count' => HuggingFaceTrendingModel::count(),
                'hot_models' => HuggingFaceTrendingModel::where('trending_score', '>=', 70)->count(),
                'news_count' => HuggingFaceModelNews::published()->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * แสดงรายการ Trending Models จาก Hugging Face
     *
     * รองรับการค้นหา, กรอง, และเรียงลำดับ
     *
     * @param Request $request
     * @return View
     */
    public function trendingModels(Request $request): View
    {
        // เริ่มต้น query
        $query = HuggingFaceTrendingModel::query();

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // กรองตามหมวดหมู่
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // กรองตามคุณสมบัติเพิ่มเติม
        if ($request->input('featured')) {
            $query->featured();
        }

        if ($request->input('recommended')) {
            $query->recommended();
        }

        if ($request->input('production')) {
            $query->productionReady();
        }

        if ($request->input('freeTier')) {
            // Models ที่สามารถรันบน free tier clouds (< $0.50/hr)
            $query->where('estimated_cost_per_hour', '<=', 0.50);
        }

        // เรียงลำดับ
        $sortBy = $request->input('sort', 'trending');
        switch ($sortBy) {
            case 'popular':
                $query->popular();
                break;
            case 'likes':
                $query->orderBy('likes', 'desc');
                break;
            case 'new':
                $query->orderBy('first_seen_at', 'desc');
                break;
            case 'cost_low':
                $query->orderBy('estimated_cost_per_hour', 'asc');
                break;
            case 'cost_high':
                $query->orderBy('estimated_cost_per_hour', 'desc');
                break;
            default: // trending
                $query->trending();
                break;
        }

        // Pagination
        $models = $query->paginate(12)->withQueryString();

        return view('admin.ai-rental.trending-models.index', compact('models'));
    }

    /**
     * แสดงข่าวสาร Hugging Face Models
     *
     * รองรับการค้นหา, กรอง, และเรียงลำดับ
     *
     * @param Request $request
     * @return View
     */
    public function news(Request $request): View
    {
        // เริ่มต้น query สำหรับข่าวที่เผยแพร่แล้ว
        $query = HuggingFaceModelNews::published();

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // กรองตามหมวดหมู่
        if ($category = $request->input('category')) {
            $query->where('categories', 'like', "%{$category}%");
        }

        // กรองตามประเภทข่าว
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        // ดึงข่าวเด่น/ปักหมุด (สูงสุด 2 ข่าว)
        $featured_news = HuggingFaceModelNews::published()
            ->where(function ($q) {
                $q->where('is_featured', true)
                  ->orWhere('is_pinned', true);
            })
            ->latest('published_at')
            ->take(2)
            ->get();

        // ดึงข่าวทั้งหมด (ไม่รวมข่าวที่แสดงใน featured)
        $news = $query
            ->whereNotIn('id', $featured_news->pluck('id'))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.ai-rental.news.index', compact('news', 'featured_news'));
    }

    /**
     * แสดงรายละเอียดข่าว
     *
     * พร้อมนับ view count
     *
     * @param HuggingFaceModelNews $news
     * @return View
     */
    public function showNews(HuggingFaceModelNews $news): View
    {
        // เพิ่ม view count
        $news->incrementViews();

        return view('admin.ai-rental.news.show', compact('news'));
    }
}
