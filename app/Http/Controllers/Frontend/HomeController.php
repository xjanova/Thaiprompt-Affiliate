<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the homepage
     */
    public function index()
    {
        // Check if system needs setup
        if (!User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        // Get featured stores (5-star or admin selected)
        $featuredStores = \App\Models\VendorStore::where(function($query) {
                $query->where('is_featured_home', true)
                      ->orWhere('rating_average', '>=', 5.0);
            })
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('featured_home_order', 'asc')
            ->orderBy('rating_average', 'desc')
            ->limit(8)
            ->get();

        // Get new products (created in last 30 days)
        $newProducts = \App\Models\Product::where('is_active', true)
            ->where('is_public_approved', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['seller', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        // Get site logo from settings
        $siteLogo = Setting::get('site_logo', asset('images/logo.svg'));

        // Get site info for hero section
        $siteInfo = [
            'name' => Setting::get('site_name', 'Thaiprompt Affiliate'),
            'tagline' => Setting::get('site_tagline', 'แพลตฟอร์มอีคอมเมิร์ซและแอฟฟิลิเอทที่ทรงพลัง'),
            'description' => Setting::get('site_description', 'ระบบครบวงจรสำหรับการขายสินค้าออนไลน์และสร้างรายได้ผ่านระบบแอฟฟิลิเอท'),
        ];

        return view('frontend.home-v3', compact(
            'featuredStores',
            'newProducts',
            'siteLogo',
            'siteInfo'
        ));
    }

    /**
     * Show the about page
     */
    public function about()
    {
        // Get version from package.json
        $version = '2.79.0'; // Default fallback
        $packageJsonPath = base_path('package.json');

        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                $version = $packageJson['version'];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\Affiliate::count(),
            'total_commissions' => \App\Models\Commission::count(),
            'total_earnings' => \App\Models\Commission::whereIn('status', ['approved', 'paid'])->sum('amount') ?? 0,
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        // Get MLM organization stats for visualization
        $mlmOrgStats = [
            'total_members' => \App\Models\MlmMember::count(),
            'active_members' => \App\Models\MlmMember::where('status', 'active')->count(),
            'total_levels' => \DB::table('mlm_genealogy')->max('depth') ?? 0,
            'monthly_growth' => \App\Models\MlmMember::whereMonth('created_at', date('m'))->count(),
        ];

        return view('frontend.about', compact('stats', 'mlmOrgStats'));
    }

    /**
     * Show the professional about page (PR version)
     */
    public function aboutProfessional()
    {
        // Get version from package.json (real-time)
        $version = '2.79.0'; // Default fallback
        $packageJsonPath = base_path('package.json');

        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                $version = $packageJson['version'];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\Affiliate::count(),
            'total_commissions' => \App\Models\Commission::count(),
            'total_earnings' => \App\Models\Commission::whereIn('status', ['approved', 'paid'])->sum('amount') ?? 0,
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        return view('frontend.about-professional', compact('stats'));
    }

    /**
     * Show the platform wiki (knowledge base)
     */
    public function platformWiki()
    {
        // Get version from CHANGELOG
        $version = '1.159.0'; // Default fallback
        $changelogPath = base_path('CHANGELOG.md');

        if (file_exists($changelogPath)) {
            $changelog = file_get_contents($changelogPath);
            // Extract latest version from CHANGELOG
            if (preg_match('/##\s*\[v?(\d+\.\d+\.\d+)\]/', $changelog, $matches)) {
                $version = $matches[1];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\Affiliate::count(),
            'total_commissions' => \App\Models\Commission::count(),
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        return view('frontend.platform-wiki', compact('stats'));
    }

    /**
     * Show the contact page
     */
    public function contact()
    {
        return view('frontend.contact');
    }

    /**
     * Show the 3D Interactive Presentation
     */
    public function presentation()
    {
        // System data for presentation
        $systems = [
            [
                'id' => 'blockchain',
                'name' => 'Blockchain & TPIX',
                'icon' => '🔗',
                'color' => '#7C3AED',
                'description' => 'Native Blockchain with Token Ecosystem',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/tpix-blockchain/README.md'
            ],
            [
                'id' => 'foodpassport',
                'name' => 'Food Passport',
                'icon' => '🌱',
                'color' => '#059669',
                'description' => 'Food Traceability & Carbon Credit System',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md'
            ],
            [
                'id' => 'games',
                'name' => 'Games & Entertainment',
                'icon' => '🎮',
                'color' => '#DC2626',
                'description' => 'Interactive Gaming Platform',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/DEMO_GAMES.md'
            ],
            [
                'id' => 'mlm',
                'name' => 'MLM System',
                'icon' => '💎',
                'color' => '#8B5CF6',
                'description' => 'Multi-Level Marketing Platform',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MLM_SYSTEM_DOCUMENTATION.md'
            ],
            [
                'id' => 'ecommerce',
                'name' => 'E-Commerce',
                'icon' => '🛒',
                'color' => '#EC4899',
                'description' => 'Multi-Vendor Marketplace',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MULTIVENDOR_DESIGN.md'
            ],
            [
                'id' => 'wallet',
                'name' => 'Digital Wallet',
                'icon' => '💰',
                'color' => '#10B981',
                'description' => 'Crypto & Fiat Payment System',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/WALLET_SYSTEM.md'
            ],
            [
                'id' => 'ai',
                'name' => 'AI Integration',
                'icon' => '🤖',
                'color' => '#F59E0B',
                'description' => 'AI-Powered Automation',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/LINE_BOT_AI_IMPLEMENTATION.md'
            ],
            [
                'id' => 'investment',
                'name' => 'Investment & Staking',
                'icon' => '📈',
                'color' => '#6366F1',
                'description' => 'Yield Farming & Staking Pools',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/TPIX_TOKEN_SYSTEM.md'
            ],
            [
                'id' => 'hotel',
                'name' => 'Hotel Booking',
                'icon' => '🏨',
                'color' => '#EF4444',
                'description' => 'Accommodation Management',
                'wiki' => '/wiki'
            ],
            [
                'id' => 'academy',
                'name' => 'Academy & Learning',
                'icon' => '🎓',
                'color' => '#14B8A6',
                'description' => 'Online Education Platform',
                'wiki' => '/wiki'
            ],
            [
                'id' => 'hrm',
                'name' => 'HRM System',
                'icon' => '👥',
                'color' => '#F97316',
                'description' => 'Human Resource Management',
                'wiki' => 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/HRM_SYSTEM_README.md'
            ],
            [
                'id' => 'platform',
                'name' => 'Platform Overview',
                'icon' => '🌐',
                'color' => '#3B82F6',
                'description' => 'Complete Ecosystem',
                'wiki' => '/about'
            ],
        ];

        return view('frontend.presentation', compact('systems'));
    }
}
