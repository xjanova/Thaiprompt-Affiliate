<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradingBot;
use App\Models\TradingBotPackage;
use App\Models\TradingBotSubscription;
use App\Models\User;
use App\Models\TradingExchange;
use App\Services\TradingEngine\TradingEngineService;
use App\Services\TradingEngine\ArbitrageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TradingBotAdminController extends Controller
{
    protected TradingEngineService $engineService;
    protected ArbitrageService $arbitrageService;

    public function __construct(
        TradingEngineService $engineService,
        ArbitrageService $arbitrageService
    ) {
        $this->middleware(['auth', 'admin']);
        $this->engineService = $engineService;
        $this->arbitrageService = $arbitrageService;
    }

    /**
     * Admin dashboard overview
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_bots' => TradingBot::where('status', 'running')->count(),
            'total_subscriptions' => TradingBotSubscription::where('status', 'active')->count(),
            'monthly_revenue' => TradingBotSubscription::where('status', 'active')
                ->join('trading_bot_packages', 'trading_bot_subscriptions.package_id', '=', 'trading_bot_packages.id')
                ->sum('trading_bot_packages.price'),
            'total_bots' => TradingBot::count(),
            'total_exchanges' => TradingExchange::count(),
        ];

        $recentSubscriptions = TradingBotSubscription::with(['user', 'package'])
            ->latest()
            ->take(10)
            ->get();

        $topPerformingBots = TradingBot::select('trading_bots.*')
            ->selectRaw('(total_profit_loss / NULLIF(allocated_capital, 0) * 100) as roi')
            ->where('status', '!=', 'error')
            ->orderByRaw('roi DESC')
            ->take(10)
            ->get();

        $systemHealth = $this->getSystemHealth();

        return view('admin.trading-bot.dashboard', compact(
            'stats',
            'recentSubscriptions',
            'topPerformingBots',
            'systemHealth'
        ));
    }

    /**
     * List all packages
     */
    public function packages()
    {
        $packages = TradingBotPackage::withCount('subscriptions')->get();

        return view('admin.trading-bot.packages.index', compact('packages'));
    }

    /**
     * Create package form
     */
    public function createPackage()
    {
        return view('admin.trading-bot.packages.create');
    }

    /**
     * Store new package
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:trading_bot_packages',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'max_bots' => 'required|integer|min:1',
            'max_exchanges' => 'required|integer|min:1',
            'max_strategies' => 'required|integer|min:1',
            'max_concurrent_trades' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $package = TradingBotPackage::create($validated);

        return redirect()
            ->route('admin.trading-bot.packages.index')
            ->with('success', 'Package created successfully');
    }

    /**
     * Edit package
     */
    public function editPackage(TradingBotPackage $package)
    {
        return view('admin.trading-bot.packages.edit', compact('package'));
    }

    /**
     * Update package
     */
    public function updatePackage(Request $request, TradingBotPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'max_bots' => 'required|integer|min:1',
            'max_exchanges' => 'required|integer|min:1',
            'max_strategies' => 'required|integer|min:1',
            'max_concurrent_trades' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $package->update($validated);

        return redirect()
            ->route('admin.trading-bot.packages.index')
            ->with('success', 'Package updated successfully');
    }

    /**
     * Delete package
     */
    public function destroyPackage(TradingBotPackage $package)
    {
        if ($package->subscriptions()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete package with active subscriptions');
        }

        $package->delete();

        return redirect()
            ->route('admin.trading-bot.packages.index')
            ->with('success', 'Package deleted successfully');
    }

    /**
     * List all subscriptions
     */
    public function subscriptions(Request $request)
    {
        $query = TradingBotSubscription::with(['user', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        $subscriptions = $query->latest()->paginate(20);
        $packages = TradingBotPackage::all();

        return view('admin.trading-bot.subscriptions.index', compact('subscriptions', 'packages'));
    }

    /**
     * View subscription details
     */
    public function showSubscription(TradingBotSubscription $subscription)
    {
        $subscription->load(['user', 'package', 'bots']);

        return view('admin.trading-bot.subscriptions.show', compact('subscription'));
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(TradingBotSubscription $subscription)
    {
        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        // Stop all associated bots
        $subscription->bots()->where('status', 'running')->each(function ($bot) {
            $this->engineService->stopBot($bot);
        });

        return redirect()
            ->back()
            ->with('success', 'Subscription canceled successfully');
    }

    /**
     * List all bots
     */
    public function bots(Request $request)
    {
        $query = TradingBot::with(['user', 'strategy', 'account.exchange']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $bots = $query->latest()->paginate(20);

        return view('admin.trading-bot.bots.index', compact('bots'));
    }

    /**
     * View bot details
     */
    public function showBot(TradingBot $bot)
    {
        $bot->load([
            'user',
            'strategy',
            'account.exchange',
            'subscription.package',
            'trades' => function ($query) {
                $query->latest()->take(50);
            },
            'signals' => function ($query) {
                $query->latest()->take(20);
            },
        ]);

        $performanceMetrics = $bot->getPerformanceMetrics();

        return view('admin.trading-bot.bots.show', compact('bot', 'performanceMetrics'));
    }

    /**
     * Force stop bot
     */
    public function stopBot(TradingBot $bot)
    {
        $this->engineService->stopBot($bot);

        return redirect()
            ->back()
            ->with('success', 'Bot stopped successfully');
    }

    /**
     * Force start bot
     */
    public function startBot(TradingBot $bot)
    {
        $this->engineService->startBot($bot);

        return redirect()
            ->back()
            ->with('success', 'Bot started successfully');
    }

    /**
     * Delete bot
     */
    public function destroyBot(TradingBot $bot)
    {
        if ($bot->status === 'running') {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete running bot. Stop it first.');
        }

        $bot->delete();

        return redirect()
            ->route('admin.trading-bot.bots.index')
            ->with('success', 'Bot deleted successfully');
    }

    /**
     * Manage exchanges
     */
    public function exchanges()
    {
        $exchanges = TradingExchange::withCount('accounts')->get();

        return view('admin.trading-bot.exchanges.index', compact('exchanges'));
    }

    /**
     * Create exchange
     */
    public function storeExchange(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:trading_exchanges',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'api_docs_url' => 'nullable|url',
            'trading_fee_percentage' => 'required|numeric|min:0|max:100',
            'withdrawal_fee' => 'nullable|numeric|min:0',
            'supported_markets' => 'required|array',
            'is_active' => 'boolean',
        ]);

        TradingExchange::create($validated);

        return redirect()
            ->route('admin.trading-bot.exchanges.index')
            ->with('success', 'Exchange added successfully');
    }

    /**
     * Update exchange
     */
    public function updateExchange(Request $request, TradingExchange $exchange)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'api_docs_url' => 'nullable|url',
            'trading_fee_percentage' => 'required|numeric|min:0|max:100',
            'withdrawal_fee' => 'nullable|numeric|min:0',
            'supported_markets' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $exchange->update($validated);

        return redirect()
            ->route('admin.trading-bot.exchanges.index')
            ->with('success', 'Exchange updated successfully');
    }

    /**
     * Analytics overview
     */
    public function analytics()
    {
        $stats = [
            'total_trades' => DB::table('trading_trades')->count(),
            'successful_trades' => DB::table('trading_trades')->where('status', 'filled')->where('profit_loss', '>', 0)->count(),
            'total_volume' => DB::table('trading_trades')->sum('quantity'),
            'total_profit' => DB::table('trading_trades')->where('status', 'filled')->sum('profit_loss'),
        ];

        // Revenue by package
        $revenueByPackage = TradingBotSubscription::where('status', 'active')
            ->join('trading_bot_packages', 'trading_bot_subscriptions.package_id', '=', 'trading_bot_packages.id')
            ->select('trading_bot_packages.name', DB::raw('SUM(trading_bot_packages.price) as total_revenue'))
            ->groupBy('trading_bot_packages.id', 'trading_bot_packages.name')
            ->get();

        // Growth metrics
        $monthlyGrowth = TradingBotSubscription::where('status', 'active')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.trading-bot.analytics', compact('stats', 'revenueByPackage', 'monthlyGrowth'));
    }

    /**
     * Arbitrage opportunities monitoring
     */
    public function arbitrageMonitor()
    {
        $opportunities = $this->arbitrageService->monitorOpportunities();

        $exchanges = TradingExchange::where('is_active', true)->get();

        return view('admin.trading-bot.arbitrage-monitor', compact('opportunities', 'exchanges'));
    }

    /**
     * System settings
     */
    public function settings()
    {
        $settings = [
            'maintenance_mode' => config('trading-bot.maintenance_mode', false),
            'max_bots_per_user' => config('trading-bot.max_bots_per_user', 10),
            'default_risk_level' => config('trading-bot.default_risk_level', 'medium'),
        ];

        return view('admin.trading-bot.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'maintenance_mode' => 'boolean',
            'max_bots_per_user' => 'integer|min:1',
            'default_risk_level' => 'in:low,medium,high',
        ]);

        // Update config or save to database
        // Implementation depends on your config management strategy

        return redirect()
            ->back()
            ->with('success', 'Settings updated successfully');
    }

    /**
     * Get system health status
     */
    protected function getSystemHealth(): array
    {
        $activeBots = TradingBot::where('status', 'running')->count();
        $errorBots = TradingBot::where('status', 'error')->count();
        $activeExchanges = TradingExchange::where('is_active', true)->count();

        $health = 'good';
        if ($errorBots > 10 || $errorBots / max(1, $activeBots) > 0.1) {
            $health = 'warning';
        }
        if ($errorBots > 50 || $activeExchanges < 2) {
            $health = 'critical';
        }

        return [
            'status' => $health,
            'active_bots' => $activeBots,
            'error_bots' => $errorBots,
            'active_exchanges' => $activeExchanges,
            'last_check' => now()->toIso8601String(),
        ];
    }
}
