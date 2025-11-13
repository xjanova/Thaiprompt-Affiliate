<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QrBarcodeController extends Controller
{
    /**
     * Display the QR Code and Barcode generator page
     */
    public function index()
    {
        return view('frontend.qr-barcode.index');
    }

    /**
     * Generate QR Code or Barcode
     * This is handled client-side, but we can log usage here
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:qrcode,barcode',
            'content' => 'required|string|max:5000',
            'format' => 'nullable|string'
        ]);

        // Log the generation for analytics (optional)
        Log::info('QR/Barcode generated', [
            'type' => $request->type,
            'format' => $request->format,
            'user_id' => auth()->id() ?? 'guest',
            'ip' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Generated successfully'
        ]);
    }

    /**
     * Get user's generation history (if authenticated)
     */
    public function history()
    {
        if (!auth()->check()) {
            return redirect()->route('qr-barcode.index')
                ->with('error', 'Please login to view history');
        }

        // In future, can fetch from database
        return view('frontend.qr-barcode.history');
    }
}
