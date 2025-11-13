<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXToken;
use App\Models\TPIXLiquidityPool;
use App\Services\TPIX\DEXService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DEX Web Controller
 * Handles web pages for DEX functionality
 */
class DEXController extends Controller
{
    public function __construct(
        protected DEXService $dexService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show swap interface
     */
    public function swap(Request $request): View
    {
        return view('user.dex.swap');
    }

    /**
     * Show liquidity pools list
     */
    public function pools(Request $request): View
    {
        return view('user.dex.pools');
    }

    /**
     * Show add liquidity form
     */
    public function addLiquidity(Request $request): View
    {
        $poolId = $request->get('pool');
        $pool = $poolId ? TPIXLiquidityPool::with(['tokenA', 'tokenB'])->find($poolId) : null;

        return view('user.dex.add-liquidity', compact('pool'));
    }

    /**
     * Show remove liquidity form
     */
    public function removeLiquidity(Request $request, int $positionId): View
    {
        $position = auth()->user()
            ->liquidityPositions()
            ->with(['pool.tokenA', 'pool.tokenB'])
            ->findOrFail($positionId);

        return view('user.dex.remove-liquidity', compact('position'));
    }

    /**
     * Show my liquidity positions
     */
    public function myPositions(Request $request): View
    {
        $positions = auth()->user()
            ->liquidityPositions()
            ->with(['pool.tokenA', 'pool.tokenB'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('user.dex.my-positions', compact('positions'));
    }

    /**
     * Show swap history
     */
    public function swapHistory(Request $request): View
    {
        $swaps = auth()->user()
            ->swaps()
            ->with(['tokenIn', 'tokenOut', 'pool'])
            ->latest()
            ->paginate(20);

        return view('user.dex.swap-history', compact('swaps'));
    }
}
