<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\RankBonus;
use App\Models\RankPromotion;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;

class RankController extends Controller
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    public function index()
    {
        $ranks = Rank::withCount(['users', 'requirements', 'bonuses'])
            ->byLevel()
            ->get();

        return view('admin.ranks.index', compact('ranks'));
    }

    public function create()
    {
        return view('admin.ranks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'level' => 'required|integer|unique:ranks',
            'color' => 'required|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_multiplier' => 'required|numeric|min:1',
            'min_points' => 'required|integer|min:0',
            'min_referrals' => 'required|integer|min:0',
            'min_sales' => 'required|numeric|min:0',
        ]);

        $rank = Rank::create($validated);

        return redirect()->route('admin.ranks.edit', $rank)
            ->with('success', 'Rank created successfully!');
    }

    public function edit(Rank $rank)
    {
        $rank->load(['requirements', 'bonuses']);
        return view('admin.ranks.edit', compact('rank'));
    }

    public function update(Request $request, Rank $rank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'level' => 'required|integer|unique:ranks,level,' . $rank->id,
            'color' => 'required|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_multiplier' => 'required|numeric|min:1',
            'min_points' => 'required|integer|min:0',
            'min_referrals' => 'required|integer|min:0',
            'min_sales' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $rank->update($validated);

        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank updated successfully!');
    }

    public function destroy(Rank $rank)
    {
        if ($rank->users()->count() > 0) {
            return back()->with('error', 'Cannot delete rank with assigned users');
        }

        $rank->delete();

        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank deleted successfully!');
    }

    public function promotions()
    {
        $promotions = RankPromotion::with(['user', 'fromRank', 'toRank'])
            ->latest()
            ->paginate(20);

        return view('admin.ranks.promotions', compact('promotions'));
    }

    public function approvePromotion(RankPromotion $promotion)
    {
        $promotion->approve(auth()->user());

        return back()->with('success', 'Promotion approved successfully!');
    }

    public function rejectPromotion(Request $request, RankPromotion $promotion)
    {
        $promotion->reject($request->input('reason'));

        return back()->with('success', 'Promotion rejected');
    }
}
