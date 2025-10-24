<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopVerification;
use App\Services\Verification\ShopVerificationService;
use Illuminate\Http\Request;

class ShopVerificationController extends Controller
{
    protected ShopVerificationService $verificationService;

    public function __construct(ShopVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of verifications
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $verifications = $this->verificationService->getAllVerifications(20, $status);
        $statistics = $this->verificationService->getVerificationStatistics();

        return view('admin.verification.index', compact('verifications', 'statistics', 'status'));
    }

    /**
     * Display pending verifications
     */
    public function pending()
    {
        $verifications = $this->verificationService->getPendingVerifications(20);

        return view('admin.verification.pending', compact('verifications'));
    }

    /**
     * Display the specified verification
     */
    public function show(ShopVerification $verification)
    {
        $verification->load(['vendor', 'user', 'verifiedBy']);
        $recommendedBadge = $this->verificationService->calculateVerificationBadge($verification);

        return view('admin.verification.show', compact('verification', 'recommendedBadge'));
    }

    /**
     * Approve verification
     */
    public function approve(Request $request, ShopVerification $verification)
    {
        $request->validate([
            'badge' => 'required|in:bronze,silver,gold,platinum',
            'admin_notes' => 'nullable|string',
        ]);

        // Update admin notes if provided
        if ($request->admin_notes) {
            $verification->update(['admin_notes' => $request->admin_notes]);
        }

        $result = $this->verificationService->approveVerification(
            $verification->id,
            auth()->user(),
            $request->badge
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.verification.show', $verification)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Reject verification
     */
    public function reject(Request $request, ShopVerification $verification)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
            'admin_notes' => 'nullable|string',
        ]);

        // Update admin notes if provided
        if ($request->admin_notes) {
            $verification->update(['admin_notes' => $request->admin_notes]);
        }

        $result = $this->verificationService->rejectVerification(
            $verification->id,
            auth()->user(),
            $request->rejection_reason
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.verification.show', $verification)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Show verification statistics
     */
    public function statistics()
    {
        $statistics = $this->verificationService->getVerificationStatistics();

        return view('admin.verification.statistics', compact('statistics'));
    }
}
