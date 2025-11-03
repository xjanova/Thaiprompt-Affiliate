<?php

namespace App\Http\Controllers;

use App\Models\AiBotProfile;
use App\Models\BotRental;
use App\Models\OwnerEarning;
use App\Models\RentalTransaction;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    private RentalService $rentalService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->rentalService = new RentalService();
    }

    /**
     * Owner dashboard overview
     */
    public function index(Request $request)
    {
        $owner = Auth::user();

        // Get owner's bots
        $bots = AiBotProfile::where('owner_id', $owner->id)
            ->where('is_rentable', true)
            ->withCount(['rentals' => function ($query) {
                $query->active();
            }])
            ->get();

        // Current month earnings summary
        $currentMonth = now()->format('Y-m');
        $earningsSummary = $this->rentalService->getOwnerEarningsSummary($owner, $currentMonth);

        // All-time summary
        $allTimeSummary = $this->rentalService->getOwnerEarningsSummary($owner);

        // Recent transactions
        $recentTransactions = RentalTransaction::whereHas('rental', function ($query) use ($owner) {
            $query->whereHas('botProfile', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            });
        })
            ->with(['rental.botProfile', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly earnings chart (last 12 months)
        $monthlyEarnings = OwnerEarning::forOwner($owner->id)
            ->where('earning_month', '>=', now()->subMonths(11)->format('Y-m'))
            ->select('earning_month', DB::raw('SUM(net_amount) as total'))
            ->groupBy('earning_month')
            ->orderBy('earning_month')
            ->get()
            ->pluck('total', 'earning_month');

        // Stats by bot
        $botStats = $bots->map(function ($bot) {
            $earnings = OwnerEarning::where('bot_profile_id', $bot->id)
                ->where('earning_month', now()->format('Y-m'))
                ->sum('net_amount');

            return [
                'bot' => $bot,
                'active_rentals' => $bot->rentals_count,
                'monthly_earnings' => $earnings,
            ];
        });

        return view('owner-dashboard.index', compact(
            'bots',
            'earningsSummary',
            'allTimeSummary',
            'recentTransactions',
            'monthlyEarnings',
            'botStats'
        ));
    }

    /**
     * Detailed earnings report
     */
    public function earnings(Request $request)
    {
        $owner = Auth::user();

        $query = OwnerEarning::forOwner($owner->id)
            ->with(['botProfile', 'rental', 'transaction']);

        // Month filter
        if ($request->filled('month')) {
            $query->forMonth($request->month);
        } else {
            // Default: current month
            $query->forMonth(now()->format('Y-m'));
        }

        // Bot filter
        if ($request->filled('bot_id')) {
            $query->where('bot_profile_id', $request->bot_id);
        }

        // Payout status filter
        if ($request->filled('payout_status')) {
            $query->where('payout_status', $request->payout_status);
        }

        $earnings = $query->orderBy('earning_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Summary for current filter
        $summary = [
            'total_gross' => $query->sum('gross_amount'),
            'total_commission' => $query->sum('commission'),
            'total_net' => $query->sum('net_amount'),
            'pending_payout' => OwnerEarning::forOwner($owner->id)
                ->pending()
                ->sum('net_amount'),
        ];

        // Get owner's bots for filter
        $bots = AiBotProfile::where('owner_id', $owner->id)
            ->where('is_rentable', true)
            ->get();

        // Available months for filter
        $availableMonths = OwnerEarning::forOwner($owner->id)
            ->select('earning_month')
            ->distinct()
            ->orderBy('earning_month', 'desc')
            ->pluck('earning_month');

        return view('owner-dashboard.earnings', compact(
            'earnings',
            'summary',
            'bots',
            'availableMonths'
        ));
    }

    /**
     * Bot rentals management
     */
    public function rentals(Request $request)
    {
        $owner = Auth::user();

        $query = BotRental::whereHas('botProfile', function ($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })->with(['botProfile', 'renter']);

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Bot filter
        if ($request->filled('bot_id')) {
            $query->where('bot_profile_id', $request->bot_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('rental_type', $request->type);
        }

        $rentals = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats
        $stats = [
            'total_rentals' => BotRental::whereHas('botProfile', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })->count(),
            'active_rentals' => BotRental::whereHas('botProfile', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })->active()->count(),
            'monthly_rentals' => BotRental::whereHas('botProfile', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })->where('rental_type', 'monthly')->active()->count(),
            'per_message_rentals' => BotRental::whereHas('botProfile', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })->where('rental_type', 'per_message')->active()->count(),
        ];

        // Get owner's bots for filter
        $bots = AiBotProfile::where('owner_id', $owner->id)
            ->where('is_rentable', true)
            ->get();

        return view('owner-dashboard.rentals', compact('rentals', 'stats', 'bots'));
    }

    /**
     * Request payout
     */
    public function requestPayout(Request $request)
    {
        $owner = Auth::user();

        $validated = $request->validate([
            'payout_method' => 'required|in:bank_transfer,promptpay',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'amount' => 'required|numeric|min:100',
        ]);

        // Check if enough pending earnings
        $pendingAmount = OwnerEarning::forOwner($owner->id)
            ->pending()
            ->sum('net_amount');

        if ($validated['amount'] > $pendingAmount) {
            return back()->with('error', 'จำนวนเงินที่ขอถอนมากกว่ายอดคงเหลือ');
        }

        // Get earnings to mark as processing
        $earnings = OwnerEarning::forOwner($owner->id)
            ->pending()
            ->orderBy('earning_date')
            ->get();

        $totalMarked = 0;
        foreach ($earnings as $earning) {
            if ($totalMarked + $earning->net_amount <= $validated['amount']) {
                $earning->update([
                    'payout_status' => 'processing',
                    'payout_method' => $validated['payout_method'],
                    'payout_reference' => 'PAYOUT-' . now()->format('YmdHis') . '-' . $owner->id,
                ]);
                $totalMarked += $earning->net_amount;

                if ($totalMarked >= $validated['amount']) {
                    break;
                }
            }
        }

        return back()->with('success', 'ส่งคำขอถอนเงินสำเร็จ จำนวน ' . number_format($totalMarked, 2) . ' บาท');
    }

    /**
     * Payout history
     */
    public function payouts(Request $request)
    {
        $owner = Auth::user();

        $query = OwnerEarning::forOwner($owner->id)
            ->whereIn('payout_status', ['processing', 'paid'])
            ->with(['botProfile']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('payout_status', $request->status);
        }

        $payouts = $query->orderBy('payout_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Summary
        $summary = [
            'total_paid' => OwnerEarning::forOwner($owner->id)->paid()->sum('net_amount'),
            'processing' => OwnerEarning::forOwner($owner->id)->processing()->sum('net_amount'),
            'pending' => OwnerEarning::forOwner($owner->id)->pending()->sum('net_amount'),
        ];

        return view('owner-dashboard.payouts', compact('payouts', 'summary'));
    }
}
