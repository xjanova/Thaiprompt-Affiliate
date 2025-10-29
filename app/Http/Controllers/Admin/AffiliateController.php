<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    /**
     * Display a listing of affiliates
     */
    public function index(Request $request)
    {
        $query = Affiliate::with(['user', 'parent.user']);

        // Search filter (name, email, referral_code)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('referral_code', 'like', '%'.$search.'%')
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Level filter
        if ($request->filled('level')) {
            $query->where('level', $request->get('level'));
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $affiliates = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.affiliates.index', compact('affiliates'));
    }

    /**
     * Display the specified affiliate
     */
    public function show(Affiliate $affiliate)
    {
        $affiliate->load(['user', 'parent.user', 'children.user', 'commissions']);
        return view('admin.affiliates.show', compact('affiliate'));
    }

    /**
     * Show affiliate tree
     */
    public function tree(Affiliate $affiliate)
    {
        $affiliate->load(['user', 'children.user', 'children.children']);
        return view('admin.affiliates.tree', compact('affiliate'));
    }

    /**
     * Show the form for editing the specified affiliate
     */
    public function edit(Affiliate $affiliate)
    {
        return view('admin.affiliates.edit', compact('affiliate'));
    }

    /**
     * Update the specified affiliate
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $affiliate->update($validated);

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate updated successfully.');
    }

    /**
     * Remove the specified affiliate
     */
    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate deleted successfully.');
    }
}
