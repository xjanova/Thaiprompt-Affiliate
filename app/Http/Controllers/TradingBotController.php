<?php

namespace App\Http\Controllers;

use App\Models\TradingBot;
use App\Models\TradingBotPackage;
use App\Models\TradingBotSubscription;
use App\Models\TradingAccount;
use App\Models\TradingStrategy;
use App\Models\TradingExchange;
use App\Services\TradingEngine\TradingEngineService;
use App\Services\TradingEngine\ArbitrageService;
use App\Services\TradingEngine\AI\EnhancedAIStrategyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradingBotController extends Controller
{
    protected TradingEngineService $engineService;
    protected ArbitrageService $arbitrageService;
    protected EnhancedAIStrategyService $aiService;

    public function __construct(
        TradingEngineService $engineService,
        ArbitrageService $arbitrageService,
        EnhancedAIStrategyService $aiService
    ) {
        $this->middleware('auth');
        $this->engineService = $engineService;
        $this->arbitrageService = $arbitrageService;
        $this->aiService = $aiService;
    }

    /**
     * Show user dashboard
     */
    public function index()
    {
        $user = Auth::user();

        $subscription = $user->tradingBotSubscription()->active()->first();

        $bots = $user->tradingBots()
            ->with(['strategy', 'account.exchange'])
            ->latest()
            ->get();

        $stats = [
            'total_bots' => $bots->count(),
            'running_bots' => $bots->where('status', 'running')->count(),
            'total_profit' => $bots->sum('net_profit'),
            'total_trades' => $user->tradingTrades()->count(),
        ];

        return view('trading-bot.index', compact('subscription', 'bots', 'stats'));
    }

    /**
     * Show marketplace
     */
    public function marketplace()
    {
        $packages = TradingBotPackage::where('is_active', true)
            ->withCount('subscriptions')
            ->get();

        $strategies = TradingStrategy::where('is_public', true)
            ->where('is_for_sale', true)
            ->with('user')
            ->paginate(12);

        return view('trading-bot.marketplace', compact('packages', 'strategies'));
    }

    /**
     * Subscribe to package
     */
    public function subscribe(Request $request, TradingBotPackage $package)
    {
        $user = Auth::user();

        // Check if already subscribed
        if ($user->tradingBotSubscription()->active()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'You already have an active subscription');
        }

        // Create subscription
        $subscription = TradingBotSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $package->billing_cycle === 'lifetime'
                ? null
                : now()->addMonth(),
        ]);

        return redirect()
            ->route('trading-bot.index')
            ->with('success', 'Successfully subscribed to ' . $package->name);
    }

    /**
     * Create bot form
     */
    public function create()
    {
        $user = Auth::user();

        // Check subscription
        $subscription = $user->tradingBotSubscription()->active()->first();
        if (!$subscription) {
            return redirect()
                ->route('trading-bot.marketplace')
                ->with('error', 'Please subscribe to a package first');
        }

        // Check bot limit
        if ($user->tradingBots()->count() >= $subscription->package->max_bots) {
            return redirect()
                ->back()
                ->with('error', 'Bot limit reached for your package');
        }

        $accounts = $user->tradingAccounts()->with('exchange')->where('status', 'active')->get();
        $strategies = $user->tradingStrategies()->where('status', 'active')->get();

        // Add public strategies
        $publicStrategies = TradingStrategy::where('is_public', true)
            ->where('is_for_sale', false)
            ->get();

        $allStrategies = $strategies->merge($publicStrategies);

        return view('trading-bot.create', compact('accounts', 'allStrategies', 'subscription'));
    }

    /**
     * Store new bot
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:trading_accounts,id',
            'strategy_id' => 'required|exists:trading_strategies,id',
            'trading_pair' => 'required|string',
            'timeframe' => 'required|in:1m,5m,15m,30m,1h,4h,1d,1w',
            'allocated_capital' => 'required|numeric|min:10',
            'position_size_percentage' => 'required|numeric|min:1|max:100',
            'dry_run' => 'boolean',
        ]);

        // Get subscription
        $subscription = $user->tradingBotSubscription()->active()->firstOrFail();

        // Parse trading pair
        [$base, $quote] = explode('/', $validated['trading_pair']);

        $bot = TradingBot::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'account_id' => $validated['account_id'],
            'strategy_id' => $validated['strategy_id'],
            'name' => $validated['name'],
            'trading_pair' => $validated['trading_pair'],
            'base_currency' => $base,
            'quote_currency' => $quote,
            'timeframe' => $validated['timeframe'],
            'allocated_capital' => $validated['allocated_capital'],
            'available_capital' => $validated['allocated_capital'],
            'position_size_percentage' => $validated['position_size_percentage'],
            'dry_run' => $validated['dry_run'] ?? true,
            'status' => 'stopped',
        ]);

        return redirect()
            ->route('trading-bot.show', $bot)
            ->with('success', 'Bot created successfully');
    }

    /**
     * Show bot details
     */
    public function show(TradingBot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load([
            'strategy',
            'account.exchange',
            'subscription.package',
        ]);

        $trades = $bot->trades()->latest()->paginate(20);
        $signals = $bot->signals()->latest()->take(10)->get();

        $performanceMetrics = $bot->getPerformanceMetrics();

        // Get arbitrage status if applicable
        $arbitrageStatus = null;
        if ($bot->strategy->strategy_type === 'arbitrage') {
            $arbitrageStatus = $this->arbitrageService->getArbitrageStatus($bot);
        }

        return view('trading-bot.show', compact(
            'bot',
            'trades',
            'signals',
            'performanceMetrics',
            'arbitrageStatus'
        ));
    }

    /**
     * Edit bot
     */
    public function edit(TradingBot $bot)
    {
        $this->authorize('update', $bot);

        $accounts = Auth::user()->tradingAccounts()->with('exchange')->where('status', 'active')->get();
        $strategies = Auth::user()->tradingStrategies()->where('status', 'active')->get();

        return view('trading-bot.edit', compact('bot', 'accounts', 'strategies'));
    }

    /**
     * Update bot
     */
    public function update(Request $request, TradingBot $bot)
    {
        $this->authorize('update', $bot);

        if ($bot->status === 'running') {
            return redirect()
                ->back()
                ->with('error', 'Cannot edit running bot. Stop it first.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allocated_capital' => 'required|numeric|min:10',
            'position_size_percentage' => 'required|numeric|min:1|max:100',
            'dry_run' => 'boolean',
        ]);

        $bot->update($validated);

        return redirect()
            ->route('trading-bot.show', $bot)
            ->with('success', 'Bot updated successfully');
    }

    /**
     * Start bot
     */
    public function start(TradingBot $bot)
    {
        $this->authorize('update', $bot);

        if ($this->engineService->startBot($bot)) {
            return redirect()
                ->back()
                ->with('success', 'Bot started successfully');
        }

        return redirect()
            ->back()
            ->with('error', 'Failed to start bot. Check logs for details.');
    }

    /**
     * Stop bot
     */
    public function stop(TradingBot $bot)
    {
        $this->authorize('update', $bot);

        if ($this->engineService->stopBot($bot)) {
            return redirect()
                ->back()
                ->with('success', 'Bot stopped successfully');
        }

        return redirect()
            ->back()
            ->with('error', 'Failed to stop bot');
    }

    /**
     * Delete bot
     */
    public function destroy(TradingBot $bot)
    {
        $this->authorize('delete', $bot);

        if ($bot->status === 'running') {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete running bot. Stop it first.');
        }

        $bot->delete();

        return redirect()
            ->route('trading-bot.index')
            ->with('success', 'Bot deleted successfully');
    }

    /**
     * Get bot analytics
     */
    public function analytics(TradingBot $bot)
    {
        $this->authorize('view', $bot);

        $performanceData = $bot->getDetailedPerformance();

        return view('trading-bot.analytics', compact('bot', 'performanceData'));
    }

    /**
     * Advanced bot configuration with TradingView charts
     */
    public function advancedConfig(TradingBot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load(['strategy', 'account.exchange']);

        return view('trading-bot.advanced-config', compact('bot'));
    }

    /**
     * Professional analytics dashboard
     */
    public function proAnalytics(TradingBot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load(['strategy', 'account.exchange']);

        // Get comprehensive performance data
        $performanceData = $bot->getDetailedPerformance();
        $trades = $bot->trades()->latest()->get();

        return view('trading-bot.pro-analytics', compact('bot', 'performanceData', 'trades'));
    }

    /**
     * Multi-exchange dashboard
     */
    public function multiExchange()
    {
        $user = Auth::user();

        // Get all user's bots with exchanges
        $bots = $user->tradingBots()
            ->with(['account.exchange', 'strategy'])
            ->get();

        // Get active exchanges
        $exchanges = TradingExchange::where('is_active', true)->get();

        // Get user's trading accounts
        $accounts = $user->tradingAccounts()->with('exchange')->get();

        return view('trading-bot.multi-exchange-dashboard', compact('bots', 'exchanges', 'accounts'));
    }

    /**
     * Risk management dashboard
     */
    public function riskManagement()
    {
        $user = Auth::user();

        // Get all user's bots with related data
        $bots = $user->tradingBots()
            ->with(['strategy', 'account.exchange'])
            ->get();

        // Calculate risk metrics
        $totalCapital = $bots->sum('allocated_capital');
        $totalProfit = $bots->sum('net_profit');
        $runningBots = $bots->where('status', 'running');

        $riskMetrics = [
            'total_capital' => $totalCapital,
            'total_profit' => $totalProfit,
            'running_bots' => $runningBots->count(),
            'bots' => $bots,
        ];

        return view('trading-bot.risk-management', compact('riskMetrics', 'bots'));
    }

    /**
     * Manage trading accounts
     */
    public function accounts()
    {
        $accounts = Auth::user()->tradingAccounts()->with('exchange')->get();
        $exchanges = TradingExchange::where('is_active', true)->get();

        return view('trading-bot.accounts.index', compact('accounts', 'exchanges'));
    }

    /**
     * Create trading account
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'exchange_id' => 'required|exists:trading_exchanges,id',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:live,demo,paper',
            'market_type' => 'required|in:spot,margin,futures',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'initial_balance' => 'required|numeric|min:0',
            'balance_currency' => 'required|string',
        ]);

        $account = TradingAccount::create([
            'user_id' => Auth::id(),
            'exchange_id' => $validated['exchange_id'],
            'account_name' => $validated['account_name'],
            'account_type' => $validated['account_type'],
            'market_type' => $validated['market_type'],
            'api_key' => encrypt($validated['api_key']),
            'api_secret' => encrypt($validated['api_secret']),
            'initial_balance' => $validated['initial_balance'],
            'current_balance' => $validated['initial_balance'],
            'balance_currency' => $validated['balance_currency'],
            'status' => 'active',
        ]);

        return redirect()
            ->route('trading-bot.accounts')
            ->with('success', 'Trading account connected successfully');
    }

    /**
     * Manage strategies
     */
    public function strategies()
    {
        $strategies = Auth::user()->tradingStrategies()->latest()->get();

        return view('trading-bot.strategies.index', compact('strategies'));
    }

    /**
     * Create strategy form
     */
    public function createStrategy()
    {
        return view('trading-bot.strategies.create');
    }

    /**
     * Store new strategy
     */
    public function storeStrategy(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strategy_type' => 'required|in:trend_following,mean_reversion,breakout,scalping,grid,arbitrage,ai_ml',
            'indicators' => 'required|array',
            'entry_conditions' => 'required|array',
            'exit_conditions' => 'nullable|array',
            'stop_loss_percentage' => 'required|numeric|min:0.1|max:50',
            'take_profit_percentage' => 'required|numeric|min:0.1|max:100',
            'use_ai' => 'boolean',
            'ai_model' => 'nullable|in:lstm,transformer,random_forest,sentiment',
        ]);

        $strategy = TradingStrategy::create([
            'user_id' => Auth::id(),
            ...$validated,
            'status' => 'active',
        ]);

        return redirect()
            ->route('trading-bot.strategies')
            ->with('success', 'Strategy created successfully');
    }
}
