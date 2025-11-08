<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\MlmProspectService;
use Illuminate\Http\Request;

class MlmProspectController extends Controller
{
    public function __construct(
        private MlmProspectService $prospectService
    ) {}

    /**
     * Display prospects list (ผู้มุ่งหวัง)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (!$mlmMember) {
            return redirect()->route('dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $status = $request->input('status');
        $prospects = $this->prospectService->getProspectsForSponsor(
            $mlmMember->id,
            $status
        );

        $stats = $this->prospectService->getProspectStats($mlmMember->id);

        return view('user.prospects.index', compact('prospects', 'stats'));
    }

    /**
     * Show create invitation link form
     */
    public function create()
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (!$mlmMember) {
            return redirect()->route('dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        return view('user.prospects.create', compact('mlmMember'));
    }

    /**
     * Generate invitation link
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (!$mlmMember) {
            return redirect()->route('dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = $this->prospectService->createInvitationLink($mlmMember);

        return redirect()->route('user.prospects.show', $prospect->id)
            ->with('success', 'สร้างลิงก์เชิญสำเร็จ!');
    }

    /**
     * Show prospect details
     */
    public function show($id)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (!$mlmMember) {
            return redirect()->route('dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = \App\Models\MlmProspect::where('id', $id)
            ->where('sponsor_mlm_member_id', $mlmMember->id)
            ->with(['registeredUser', 'sponsorUser'])
            ->firstOrFail();

        return view('user.prospects.show', compact('prospect'));
    }
}
