<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Affiliate;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Exception;

class CommissionController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display a listing of commissions
     */
    public function index(Request $request)
    {
        $query = Commission::with(['affiliate.user', 'user']);

        // Search filter (name, email)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('affiliate.user', function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $commissions = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.commissions.index', compact('commissions'));
    }

    /**
     * Show the form for creating a new commission
     */
    public function create()
    {
        // Get all active affiliates with their users
        $affiliates = Affiliate::with('user')
            ->where('status', 'active')
            ->get();

        // Get all users for the user_id field
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.commissions.create', compact('affiliates', 'users'));
    }

    /**
     * Store a newly created commission in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'affiliate_id' => ['required', 'exists:affiliates,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', 'string', 'in:referral,direct_sales,team_sales,bonus'],
            'status' => ['nullable', 'in:pending,approved,rejected,paid'],
            'notes' => ['nullable', 'string'],
        ]);

        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'pending';
        }

        $commission = Commission::create($validated);

        return redirect()->route('admin.commissions.index')
            ->with('success', 'Commission created successfully.');
    }

    /**
     * Display the specified commission
     */
    public function show(Commission $commission)
    {
        $commission->load(['affiliate.user', 'user']);
        return view('admin.commissions.show', compact('commission'));
    }

    /**
     * Approve a commission
     */
    public function approve(Commission $commission)
    {
        if ($commission->status !== 'pending') {
            return back()->with('error', 'Only pending commissions can be approved.');
        }

        $commission->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Commission approved successfully.');
    }

    /**
     * Reject a commission
     */
    public function reject(Commission $commission)
    {
        if ($commission->status !== 'pending') {
            return back()->with('error', 'Only pending commissions can be rejected.');
        }

        $commission->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'Commission rejected successfully.');
    }

    /**
     * Mark commission as paid
     */
    public function pay(Commission $commission)
    {
        if ($commission->status !== 'approved') {
            return back()->with('error', 'Only approved commissions can be marked as paid.');
        }

        try {
            // Get wallet for the user who earned the commission
            $user = $commission->user ?? $commission->affiliate->user;

            if (!$user) {
                return back()->with('error', 'User not found for this commission.');
            }

            $wallet = $this->walletService->getOrCreateWallet($user);

            // Add commission to wallet
            $transaction = $this->walletService->addCommission(
                $wallet,
                $commission->amount,
                $commission->id,
                "Commission #{$commission->id} - {$commission->type}"
            );

            // Update commission status
            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return back()->with('success', 'Commission paid successfully. Amount added to wallet: ' . number_format($commission->amount, 2) . ' THB');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to pay commission: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified commission
     */
    public function edit(Commission $commission)
    {
        return view('admin.commissions.edit', compact('commission'));
    }

    /**
     * Update the specified commission
     */
    public function update(Request $request, Commission $commission)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,paid'],
            'notes' => ['nullable', 'string'],
        ]);

        // If changing to paid status, add money to wallet
        if ($validated['status'] === 'paid' && $commission->status !== 'paid') {
            try {
                $user = $commission->user ?? $commission->affiliate->user;

                if (!$user) {
                    return back()->with('error', 'User not found for this commission.');
                }

                $wallet = $this->walletService->getOrCreateWallet($user);

                // Add commission to wallet
                $this->walletService->addCommission(
                    $wallet,
                    $commission->amount,
                    $commission->id,
                    "Commission #{$commission->id} - {$commission->type}"
                );

                $validated['paid_at'] = now();
            } catch (Exception $e) {
                return back()->with('error', 'Failed to update commission: ' . $e->getMessage());
            }
        }

        // If changing to rejected status
        if ($validated['status'] === 'rejected' && $commission->status !== 'rejected') {
            $validated['rejected_at'] = now();
        }

        $commission->update($validated);

        return redirect()->route('admin.commissions.index')
            ->with('success', 'Commission updated successfully.');
    }

    /**
     * Remove the specified commission
     */
    public function destroy(Commission $commission)
    {
        $commission->delete();

        return redirect()->route('admin.commissions.index')
            ->with('success', 'Commission deleted successfully.');
    }

    /**
     * Bulk approve commissions
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'commission_ids' => ['required', 'array'],
            'commission_ids.*' => ['required', 'exists:commissions,id'],
        ]);

        $count = 0;
        foreach ($validated['commission_ids'] as $commissionId) {
            $commission = Commission::find($commissionId);

            if ($commission && $commission->status === 'pending') {
                $commission->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                $count++;
            }
        }

        return back()->with('success', "Successfully approved {$count} commission(s).");
    }

    /**
     * Bulk reject commissions
     */
    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'commission_ids' => ['required', 'array'],
            'commission_ids.*' => ['required', 'exists:commissions,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $count = 0;
        foreach ($validated['commission_ids'] as $commissionId) {
            $commission = Commission::find($commissionId);

            if ($commission && $commission->status === 'pending') {
                $commission->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'notes' => $validated['reason'] ?? $commission->notes,
                ]);
                $count++;
            }
        }

        return back()->with('success', "Successfully rejected {$count} commission(s).");
    }
}
