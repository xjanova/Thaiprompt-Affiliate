<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\Verification\ShopVerificationService;
use Illuminate\Http\Request;

class VendorVerificationController extends Controller
{
    protected ShopVerificationService $verificationService;

    public function __construct(ShopVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
        $this->middleware(['auth', 'role:vendor']);
    }

    /**
     * Show verification status and form
     */
    public function index()
    {
        $vendor = auth()->user()->vendor;

        if (!$vendor) {
            return redirect()->route('vendor.dashboard')
                ->with('error', 'Vendor profile not found');
        }

        $verificationStatus = $this->verificationService->getVerificationStatus($vendor);

        return view('vendor.verification.index', compact('verificationStatus', 'vendor'));
    }

    /**
     * Show verification form
     */
    public function create()
    {
        $vendor = auth()->user()->vendor;

        if (!$vendor) {
            return redirect()->route('vendor.dashboard')
                ->with('error', 'Vendor profile not found');
        }

        // Check if already verified
        if ($vendor->is_verified) {
            return redirect()->route('vendor.verification.index')
                ->with('info', 'Your shop is already verified');
        }

        return view('vendor.verification.create', compact('vendor'));
    }

    /**
     * Submit verification request
     */
    public function store(Request $request)
    {
        $vendor = auth()->user()->vendor;

        if (!$vendor) {
            return redirect()->route('vendor.dashboard')
                ->with('error', 'Vendor profile not found');
        }

        $request->validate([
            'business_registration_number' => 'nullable|string|max:255',
            'business_registration_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tax_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'business_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_card_number' => 'required|string|max:20',
            'id_card_front' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'id_card_back' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'selfie_with_id' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'business_type' => 'required|string|max:255',
            'business_address' => 'required|string',
            'business_phone' => 'required|string|max:20',
            'business_email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'bank_statement' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank_book_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'additional_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
        ]);

        $result = $this->verificationService->submitVerification(
            $vendor,
            $request->all()
        );

        if ($result['success']) {
            return redirect()
                ->route('vendor.verification.index')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Show verification details
     */
    public function show()
    {
        $vendor = auth()->user()->vendor;

        if (!$vendor) {
            return redirect()->route('vendor.dashboard')
                ->with('error', 'Vendor profile not found');
        }

        $verification = $vendor->verifications()->latest()->first();

        if (!$verification) {
            return redirect()->route('vendor.verification.create')
                ->with('info', 'Please submit your verification documents');
        }

        return view('vendor.verification.show', compact('verification', 'vendor'));
    }
}
