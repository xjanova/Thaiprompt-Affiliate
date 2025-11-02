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
     * Show all affiliates tree view (old HTML version)
     */
    public function treeView()
    {
        // Get root affiliates (no parent) with recursive children loading
        $affiliates = Affiliate::with($this->getRecursiveRelations())
            ->whereNull('parent_id')
            ->get();

        return view('admin.affiliates.tree-all', compact('affiliates'));
    }

    /**
     * Show interactive tree view with D3.js (new version)
     */
    public function treeViewInteractive()
    {
        // Get root affiliates for the dropdown selector
        $rootAffiliates = Affiliate::with(['user', 'rank'])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.affiliates.tree-new', compact('rootAffiliates'));
    }

    /**
     * Get recursive relations for tree loading
     */
    private function getRecursiveRelations($depth = 10)
    {
        $relations = ['user'];
        $prefix = 'children';

        for ($i = 0; $i < $depth; $i++) {
            $relations[] = $prefix . '.user';
            $prefix .= '.children';
        }

        return $relations;
    }

    /**
     * Show affiliate tree
     */
    public function tree(Affiliate $affiliate)
    {
        // Load affiliate with recursive children
        $affiliate->load($this->getRecursiveRelations());
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

    /**
     * Move affiliate to new parent
     */
    public function move(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'new_parent_id' => ['nullable', 'exists:affiliates,id'],
        ]);

        $newParentId = $validated['new_parent_id'];

        // Validation: Cannot move to self
        if ($newParentId == $affiliate->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถย้ายไปยังตัวเองได้'
            ], 400);
        }

        // Validation: Cannot move to own descendant
        if ($newParentId && $this->isDescendant($affiliate->id, $newParentId)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถย้ายไปยังลูกสายของตัวเองได้'
            ], 400);
        }

        // Update parent
        $affiliate->parent_id = $newParentId;
        $affiliate->save();

        return response()->json([
            'success' => true,
            'message' => 'ย้ายสายงานสำเร็จ'
        ]);
    }

    /**
     * Check if target is a descendant of source
     */
    private function isDescendant($sourceId, $targetId)
    {
        $target = Affiliate::find($targetId);
        while ($target) {
            if ($target->parent_id == $sourceId) {
                return true;
            }
            $target = $target->parent;
        }
        return false;
    }
}
