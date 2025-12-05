<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiBotRental;
use App\Models\OwnerEarning;
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
     *
     * ดึงข้อมูลการเช่าบอทของผู้ใช้พร้อมสถิติ
     */
    public function myRentals()
    {
        $user = Auth::user();

        // ดึงการเช่าพร้อม botProfile และ owner ผ่าน botProfile
        $rentals = AiBotRental::where('renter_id', $user->id)
            ->with(['botProfile.owner'])
            ->latest()
            ->paginate(12);

        // คำนวณสถิติการเช่า
        $stats = [
            'total_rentals' => AiBotRental::where('renter_id', $user->id)->count(),
            'active_rentals' => AiBotRental::where('renter_id', $user->id)->active()->count(),
            'total_spent' => AiBotRental::where('renter_id', $user->id)->sum('total_amount'),
        ];

        return view('chatbot.marketplace.my-rentals', compact('rentals', 'stats'));
    }

    /**
     * หน้าแสดงรายได้จากการให้เช่าบอท
     *
     * ดึงข้อมูลรายได้จากการให้เช่าบอทของเจ้าของ
     * โดยค้นหาผ่าน botProfile ที่ผู้ใช้เป็นเจ้าของ
     */
    public function myEarnings()
    {
        $user = Auth::user();

        // ดึง bot_profile_ids ที่ผู้ใช้เป็นเจ้าของ
        $ownedBotIds = AiBotProfile::where('owner_id', $user->id)->pluck('id');

        // ดึงการเช่าที่เป็นบอทของผู้ใช้
        $rentals = AiBotRental::whereIn('bot_profile_id', $ownedBotIds)
            ->with(['botProfile', 'renter'])
            ->latest()
            ->paginate(12);

        // คำนวณสถิติรายได้จาก OwnerEarning model
        $stats = [
            'total_rentals' => AiBotRental::whereIn('bot_profile_id', $ownedBotIds)->count(),
            'active_rentals' => AiBotRental::whereIn('bot_profile_id', $ownedBotIds)->active()->count(),
            'total_earnings' => OwnerEarning::where('owner_id', $user->id)->sum('net_amount'),
            'this_month_earnings' => OwnerEarning::where('owner_id', $user->id)
                ->where('earning_month', now()->format('Y-m'))
                ->sum('net_amount'),
        ];

        return view('chatbot.marketplace.my-earnings', compact('rentals', 'stats'));
    }
}
