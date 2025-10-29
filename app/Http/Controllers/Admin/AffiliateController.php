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
    public function index()
    {
        $affiliates = Affiliate::with(['user', 'parent.user'])
            ->latest()
            ->paginate(20);

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
