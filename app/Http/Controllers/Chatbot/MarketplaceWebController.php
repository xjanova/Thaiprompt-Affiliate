<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiBotRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Marketplace Web Controller
 *
 * Frontend controller สำหรับ Marketplace
 */
class MarketplaceWebController extends Controller
{
    /**
     * หน้า Marketplace - ดูบอททั้งหมดที่ให้เช่า
     */
    public function index(Request $request)
    {
        $query = AiBotProfile::public()
            ->rentable()
            ->with(['owner', 'provider', 'model'])
            ->withCount('activeRentals');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        if ($sortBy === 'popular') {
            $query->orderBy('active_rentals_count', 'desc');
        } elseif ($sortBy === 'price') {
            $query->orderBy('rental_price_per_month', $request->get('sort_order', 'asc'));
        } else {
            $query->orderBy($sortBy, 'desc');
        }

        $bots = $query->paginate(12);

        return view('chatbot.marketplace.index', compact('bots'));
    }

    /**
     * รายละเอียดบอทใน Marketplace
     */
    public function show($id)
    {
        $bot = AiBotProfile::public()
            ->rentable()
            ->with(['owner', 'provider', 'model'])
            ->withCount('activeRentals')
            ->findOrFail($id);

        // Check if current user already rented
        $hasRented = false;
        if (Auth::check()) {
            $hasRented = AiBotRental::where('bot_profile_id', $bot->id)
                ->where('renter_id', Auth::id())
                ->active()
                ->exists();
        }

        return view('chatbot.marketplace.show', compact('bot', 'hasRented'));
    }

    /**
     * หน้าแสดงบอทที่เช่าอยู่
     */
    public function myRentals()
    {
        $user = Auth::user();

        $rentals = AiBotRental::where('renter_id', $user->id)
            ->with(['botProfile', 'owner'])
            ->latest()
            ->paginate(12);

        $stats = [
            'total_rentals' => AiBotRental::where('renter_id', $user->id)->count(),
            'active_rentals' => AiBotRental::where('renter_id', $user->id)->active()->count(),
            'total_spent' => AiBotRental::where('renter_id', $user->id)->sum('total_cost'),
        ];

        return view('chatbot.marketplace.my-rentals', compact('rentals', 'stats'));
    }

    /**
     * หน้าแสดงรายได้จากการให้เช่าบอท
     */
    public function myEarnings()
    {
        $user = Auth::user();

        $rentals = AiBotRental::where('owner_id', $user->id)
            ->with(['botProfile', 'renter'])
            ->latest()
            ->paginate(12);

        $stats = [
            'total_rentals' => AiBotRental::where('owner_id', $user->id)->count(),
            'active_rentals' => AiBotRental::where('owner_id', $user->id)->active()->count(),
            'total_earnings' => AiBotRental::where('owner_id', $user->id)->sum('owner_earning'),
            'this_month_earnings' => AiBotRental::where('owner_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->sum('owner_earning'),
        ];

        return view('chatbot.marketplace.my-earnings', compact('rentals', 'stats'));
    }
}
