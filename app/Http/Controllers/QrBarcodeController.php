<?php

namespace App\Http\Controllers;

use App\Models\QrBarcode;
use App\Models\QrBarcodeTemplate;
use App\Models\QrBarcodeScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QrBarcodeController extends Controller
{
    /**
     * Display the QR Code and Barcode generator page
     */
    public function index()
    {
        $templates = QrBarcodeTemplate::public()->popular(12)->get();
        $recentCodes = null;

        if (auth()->check()) {
            $recentCodes = QrBarcode::where('user_id', auth()->id())
                ->recent(6)
                ->get();
        }

        return view('frontend.qr-barcode.index', compact('templates', 'recentCodes'));
    }

    /**
     * Save generated QR Code or Barcode
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:qrcode,barcode',
            'format' => 'required|string',
            'content' => 'required|string|max:5000',
            'settings' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'image_data' => 'nullable|string', // Base64 image data
        ]);

        $qrBarcode = new QrBarcode($request->except('image_data'));
        $qrBarcode->user_id = auth()->id();

        // Save image if provided
        if ($request->has('image_data')) {
            $imageData = $request->image_data;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $image = base64_decode($imageData);

            $filename = 'qr-barcodes/' . Str::random(40) . '.png';
            Storage::disk('public')->put($filename, $image);
            $qrBarcode->file_path = $filename;

            // Generate thumbnail
            $thumbnailFilename = 'qr-barcodes/thumbnails/' . Str::random(40) . '.png';
            // Simple thumbnail (in production, use image manipulation library)
            Storage::disk('public')->put($thumbnailFilename, $image);
            $qrBarcode->thumbnail_path = $thumbnailFilename;
        }

        $qrBarcode->save();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกสำเร็จ!',
            'data' => $qrBarcode
        ]);
    }

    /**
     * Get user's QR/Barcode history
     */
    public function history()
    {
        if (!auth()->check()) {
            return redirect()->route('qr-barcode.index')
                ->with('error', 'กรุณาเข้าสู่ระบบเพื่อดูประวัติ');
        }

        $codes = QrBarcode::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => QrBarcode::where('user_id', auth()->id())->count(),
            'qrcodes' => QrBarcode::where('user_id', auth()->id())->where('type', 'qrcode')->count(),
            'barcodes' => QrBarcode::where('user_id', auth()->id())->where('type', 'barcode')->count(),
            'favorites' => QrBarcode::where('user_id', auth()->id())->where('is_favorite', true)->count(),
            'total_scans' => QrBarcode::where('user_id', auth()->id())->sum('scan_count'),
        ];

        return view('frontend.qr-barcode.history', compact('codes', 'stats'));
    }

    /**
     * Show single QR/Barcode details
     */
    public function show($id)
    {
        $code = QrBarcode::findOrFail($id);

        // Check permission
        if (!$code->is_public && (!auth()->check() || $code->user_id !== auth()->id())) {
            abort(403, 'Unauthorized access');
        }

        $scanAnalytics = $this->getAnalytics($code);

        return view('frontend.qr-barcode.show', compact('code', 'scanAnalytics'));
    }

    /**
     * Update QR/Barcode
     */
    public function update(Request $request, $id)
    {
        $code = QrBarcode::findOrFail($id);

        // Check permission
        if (!auth()->check() || $code->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
        ]);

        $code->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสำเร็จ!',
            'data' => $code
        ]);
    }

    /**
     * Delete QR/Barcode
     */
    public function destroy($id)
    {
        $code = QrBarcode::findOrFail($id);

        // Check permission
        if (!auth()->check() || $code->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete files
        if ($code->file_path) {
            Storage::disk('public')->delete($code->file_path);
        }
        if ($code->thumbnail_path) {
            Storage::disk('public')->delete($code->thumbnail_path);
        }

        $code->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบสำเร็จ!'
        ]);
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite($id)
    {
        $code = QrBarcode::findOrFail($id);

        if (!auth()->check() || $code->user_id !== auth()->id()) {
            abort(403);
        }

        $code->is_favorite = !$code->is_favorite;
        $code->save();

        return response()->json([
            'success' => true,
            'is_favorite' => $code->is_favorite
        ]);
    }

    /**
     * Batch generation
     */
    public function batchGenerate(Request $request)
    {
        $request->validate([
            'items' => 'required|array|max:100',
            'items.*.type' => 'required|in:qrcode,barcode',
            'items.*.format' => 'required|string',
            'items.*.content' => 'required|string|max:5000',
            'items.*.title' => 'nullable|string|max:255',
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'กรุณาเข้าสู่ระบบ'], 401);
        }

        $results = [];
        foreach ($request->items as $item) {
            $qrBarcode = QrBarcode::create([
                'user_id' => auth()->id(),
                'type' => $item['type'],
                'format' => $item['format'],
                'content' => $item['content'],
                'title' => $item['title'] ?? null,
                'settings' => $item['settings'] ?? [],
            ]);
            $results[] = $qrBarcode;
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างสำเร็จ ' . count($results) . ' รายการ',
            'data' => $results
        ]);
    }

    /**
     * QR Scanner page
     */
    public function scanner()
    {
        return view('frontend.qr-barcode.scanner');
    }

    /**
     * Decode scanned QR/Barcode
     */
    public function decode(Request $request)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        return response()->json([
            'success' => true,
            'content' => $request->content,
            'decoded' => $this->parseContent($request->content)
        ]);
    }

    /**
     * Templates page
     */
    public function templates()
    {
        $templates = QrBarcodeTemplate::public()->paginate(24);
        return view('frontend.qr-barcode.templates', compact('templates'));
    }

    /**
     * Gallery page (public QR/Barcodes)
     */
    public function gallery()
    {
        $codes = QrBarcode::public()
            ->with('user')
            ->orderBy('scan_count', 'desc')
            ->paginate(24);

        return view('frontend.qr-barcode.gallery', compact('codes'));
    }

    /**
     * Analytics page
     */
    public function analytics()
    {
        if (!auth()->check()) {
            return redirect()->route('qr-barcode.index');
        }

        $codes = QrBarcode::where('user_id', auth()->id())
            ->with('scans')
            ->get();

        $totalScans = $codes->sum('scan_count');
        $scansByDevice = [];
        $scansByCountry = [];
        $scansByDate = [];

        foreach ($codes as $code) {
            foreach ($code->scans as $scan) {
                // Group by device
                $device = $scan->device_type ?? 'unknown';
                $scansByDevice[$device] = ($scansByDevice[$device] ?? 0) + 1;

                // Group by country
                $country = $scan->country ?? 'unknown';
                $scansByCountry[$country] = ($scansByCountry[$country] ?? 0) + 1;

                // Group by date
                $date = $scan->scanned_at->format('Y-m-d');
                $scansByDate[$date] = ($scansByDate[$date] ?? 0) + 1;
            }
        }

        return view('frontend.qr-barcode.analytics', compact(
            'codes',
            'totalScans',
            'scansByDevice',
            'scansByCountry',
            'scansByDate'
        ));
    }

    /**
     * Short URL redirect
     */
    public function redirect($shortUrl)
    {
        $code = QrBarcode::where('short_url', $shortUrl)->firstOrFail();

        // Track scan
        $this->trackScan($code, request());

        // Redirect based on content
        if ($code->format === 'url') {
            return redirect($code->content);
        }

        // Show content page
        return view('frontend.qr-barcode.redirect', compact('code'));
    }

    /**
     * Track scan
     */
    protected function trackScan(QrBarcode $code, Request $request)
    {
        $agent = new \Jenssegers\Agent\Agent();

        QrBarcodeScan::create([
            'qr_barcode_id' => $code->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'referrer' => $request->header('referer'),
            'scanned_at' => now(),
        ]);

        $code->incrementScanCount();
    }

    /**
     * Get analytics for a code
     */
    protected function getAnalytics(QrBarcode $code)
    {
        $scans = $code->scans;

        return [
            'total_scans' => $code->scan_count,
            'last_scanned' => $code->last_scanned_at,
            'devices' => $scans->groupBy('device_type')->map->count(),
            'browsers' => $scans->groupBy('browser')->map->count(),
            'countries' => $scans->groupBy('country')->map->count(),
            'daily_scans' => $scans->groupBy(function ($scan) {
                return $scan->scanned_at->format('Y-m-d');
            })->map->count(),
        ];
    }

    /**
     * Parse content to detect type
     */
    protected function parseContent($content)
    {
        $result = ['type' => 'text', 'data' => $content];

        if (filter_var($content, FILTER_VALIDATE_URL)) {
            $result['type'] = 'url';
        } elseif (filter_var($content, FILTER_VALIDATE_EMAIL)) {
            $result['type'] = 'email';
        } elseif (preg_match('/^tel:/i', $content)) {
            $result['type'] = 'phone';
            $result['data'] = str_replace('tel:', '', $content);
        } elseif (preg_match('/^WIFI:/i', $content)) {
            $result['type'] = 'wifi';
            // Parse WiFi format
        } elseif (preg_match('/^BEGIN:VCARD/i', $content)) {
            $result['type'] = 'vcard';
        }

        return $result;
    }
}
