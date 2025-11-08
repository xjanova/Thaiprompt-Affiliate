<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmProspect;
use App\Services\MlmProspectService;
use Illuminate\Http\Request;

class MlmProspectController extends Controller
{
    public function __construct(
        private MlmProspectService $prospectService
    ) {}

    /**
     * Display all prospects
     */
    public function index(Request $request)
    {
        $query = MlmProspect::with(['sponsorUser', 'sponsorMember', 'registeredUser'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by sponsor
        if ($request->has('sponsor_id')) {
            $query->where('sponsor_user_id', $request->input('sponsor_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('line_display_name', 'like', "%{$search}%")
                  ->orWhere('referral_token', 'like', "%{$search}%")
                  ->orWhere('line_user_id', 'like', "%{$search}%");
            });
        }

        $prospects = $query->paginate(50);

        // Get stats
        $stats = [
            'total' => MlmProspect::count(),
            'pending' => MlmProspect::where('status', 'pending')->count(),
            'in_progress' => MlmProspect::where('status', 'in_progress')->count(),
            'completed' => MlmProspect::where('status', 'completed')->count(),
            'expired' => MlmProspect::where('status', 'expired')->count(),
        ];

        return view('admin.mlm-prospects.index', compact('prospects', 'stats'));
    }

    /**
     * Show prospect details
     */
    public function show($id)
    {
        $prospect = MlmProspect::with(['sponsorUser', 'sponsorMember', 'registeredUser'])
            ->findOrFail($id);

        return view('admin.mlm-prospects.show', compact('prospect'));
    }

    /**
     * Run expire job manually
     */
    public function expireOld()
    {
        $count = $this->prospectService->expireOldProspects();

        return redirect()->back()
            ->with('success', "หมดอายุผู้มุ่งหวัง {$count} รายการ");
    }
}
