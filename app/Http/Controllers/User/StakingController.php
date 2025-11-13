<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXStakingPool;
use App\Models\TPIXStake;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staking Web Controller
 * Handles web pages for staking functionality
 */
class StakingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show staking pools and user stakes
     */
    public function index(Request $request): View
    {
        $pools = TPIXStakingPool::with('token')
            ->where('status', 'active')
            ->orderBy('apy', 'desc')
            ->get();

        $myStakes = auth()->user()
            ->stakes()
            ->with(['pool.token'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('user.staking.index', compact('pools', 'myStakes'));
    }

    /**
     * Show staking pool details
     */
    public function show(Request $request, int $poolId): View
    {
        $pool = TPIXStakingPool::with('token')->findOrFail($poolId);

        $myStake = auth()->user()
            ->stakes()
            ->where('pool_id', $poolId)
            ->where('status', 'active')
            ->first();

        return view('user.staking.show', compact('pool', 'myStake'));
    }

    /**
     * Show stake form
     */
    public function stakeForm(Request $request, int $poolId): View
    {
        $pool = TPIXStakingPool::with('token')->findOrFail($poolId);

        // Get user's token balance
        $balance = auth()->user()
            ->tokenBalances()
            ->where('token_id', $pool->token_id)
            ->first();

        return view('user.staking.stake', compact('pool', 'balance'));
    }

    /**
     * Show my staking history
     */
    public function history(Request $request): View
    {
        $stakes = auth()->user()
            ->stakes()
            ->with(['pool.token'])
            ->latest()
            ->paginate(20);

        return view('user.staking.history', compact('stakes'));
    }
}
