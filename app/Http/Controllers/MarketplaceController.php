<?php

namespace App\Http\Controllers;

use App\Models\AiBotProfile;
use App\Models\BotRental;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    private RentalService $rentalService;

    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
        $this->rentalService = new RentalService();
    }

    /**
     * Display marketplace - browse available bots
     */
    public function index(Request $request)
    {
        $query = AiBotProfile::query()
            ->where('is_active', true)
            ->where('is_rentable', true)
            ->with(['owner', 'provider']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('personality', 'like', "%{$search}%");
            });
        }

        // Rental type filter
        if ($request->filled('rental_type')) {
            if ($request->rental_type === 'monthly') {
                $query->whereNotNull('rental_price_per_month');
            } elseif ($request->rental_type === 'per_message') {
                $query->whereNotNull('rental_price_per_message');
            }
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('rental_price_per_month', '>=', $request->min_price)
                    ->orWhere('rental_price_per_message', '>=', $request->min_price);
            });
        }

        if ($request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('rental_price_per_month', '<=', $request->max_price)
                    ->orWhere('rental_price_per_message', '<=', $request->max_price);
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('rental_price_per_month', 'asc');
                break;
            case 'price_high':
                $query->orderBy('rental_price_per_month', 'desc');
                break;
            case 'popular':
                $query->withCount('rentals')->orderBy('rentals_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortDir);
        }

        $bots = $query->paginate(12)->withQueryString();

        // Categories for filter
        $categories = AiBotProfile::distinct()
            ->where('is_rentable', true)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('marketplace.index', compact('bots', 'categories'));
    }

    /**
     * Show bot details
     */
    public function show($id)
    {
        $bot = AiBotProfile::where('is_active', true)
            ->where('is_rentable', true)
            ->with(['owner', 'provider', 'knowledgeBases'])
            ->findOrFail($id);

        // Check if current user already rented this bot
        $existingRental = null;
        if (Auth::check()) {
            $existingRental = BotRental::where('bot_profile_id', $bot->id)
                ->where('renter_id', Auth::id())
                ->active()
                ->first();
        }

        // Get rental stats
        $rentalStats = [
            'total_rentals' => $bot->rentals()->count(),
            'active_rentals' => $bot->rentals()->active()->count(),
            'avg_rating' => 0, // TODO: Add rating system later
            'total_messages' => $bot->rentals()->sum('total_messages'),
        ];

        // Sample reviews (TODO: implement reviews system)
        $reviews = collect();

        return view('marketplace.show', compact('bot', 'existingRental', 'rentalStats', 'reviews'));
    }

    /**
     * Process rental request
     */
    public function rent(Request $request, $id)
    {
        $bot = AiBotProfile::where('is_active', true)
            ->where('is_rentable', true)
            ->findOrFail($id);

        // Check if user can rent
        if (!$this->rentalService->canUserRentBot($bot, Auth::user())) {
            return back()->with('error', 'คุณไม่สามารถเช่าบอทนี้ได้');
        }

        $validated = $request->validate([
            'rental_type' => 'required|in:monthly,per_message',
            'payment_method' => 'required|in:wallet,credit_card',
            'accept_terms' => 'required|accepted',
        ]);

        $rentalType = $validated['rental_type'];
        $paymentMethod = $validated['payment_method'];

        // Process rental based on type
        if ($rentalType === 'monthly') {
            if (!$bot->rental_price_per_month) {
                return back()->with('error', 'บอทนี้ไม่รองรับการเช่ารายเดือน');
            }

            $result = $this->rentalService->rentMonthly($bot, Auth::user(), $paymentMethod);
        } else {
            if (!$bot->rental_price_per_message) {
                return back()->with('error', 'บอทนี้ไม่รองรับการเช่าแบบต่อข้อความ');
            }

            $result = $this->rentalService->rentPerMessage($bot, Auth::user());
        }

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('marketplace.rent-success', $result['rental']->id)
            ->with('success', 'เช่าบอทสำเร็จ!');
    }

    /**
     * Show rental success page
     */
    public function rentSuccess($rentalId)
    {
        $rental = BotRental::with(['botProfile', 'renter'])
            ->where('renter_id', Auth::id())
            ->findOrFail($rentalId);

        return view('marketplace.rent-success', compact('rental'));
    }
}
