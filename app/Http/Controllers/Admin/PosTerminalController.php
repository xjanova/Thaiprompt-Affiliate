<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosApiKey;
use App\Models\PosTerminal;
use App\Models\VendorStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin Controller สำหรับจัดการ POS Terminals และ API Keys
 *
 * ฟีเจอร์:
 * - แสดงรายการ POS Terminals พร้อมสถานะ Online/Offline
 * - จัดการ API Keys (สร้าง, บล็อก, ปลดบล็อก)
 * - ดูประวัติการ Sync
 * - สถิติการใช้งาน
 */
class PosTerminalController extends Controller
{
    /**
     * หน้าแสดงรายการ POS Terminals ทั้งหมด
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = PosTerminal::with(['shop', 'apiKey'])
            ->withCount('dailyReports');

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_key', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%")
                  ->orWhereHas('shop', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter ตาม shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter ตาม status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter Online/Offline
        if ($request->filled('online')) {
            $threshold = Carbon::now()->subMinutes(5); // ถ้า sync ภายใน 5 นาที = Online
            if ($request->online === 'online') {
                $query->where('last_sync_at', '>=', $threshold);
            } else {
                $query->where(fn($q) => $q->whereNull('last_sync_at')
                    ->orWhere('last_sync_at', '<', $threshold));
            }
        }

        $terminals = $query->latest('last_sync_at')
            ->paginate(20)
            ->withQueryString();

        $shops = VendorStore::orderBy('name')->get();

        // สถิติ
        $stats = $this->getStats();

        return view('admin.pos-terminals.index', compact('terminals', 'shops', 'stats'));
    }

    /**
     * หน้าแสดงรายละเอียด Terminal
     *
     * @param PosTerminal $terminal
     * @return View
     */
    public function show(PosTerminal $terminal): View
    {
        $terminal->load(['shop', 'apiKey', 'dailyReports' => fn($q) => $q->latest()->limit(30)]);

        return view('admin.pos-terminals.show', compact('terminal'));
    }

    /**
     * หน้าจัดการ API Keys
     *
     * @param Request $request
     * @return View
     */
    public function apiKeys(Request $request): View
    {
        $query = PosApiKey::with(['shop', 'terminals'])
            ->withCount('terminals');

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('shop', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter ตาม shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter ตาม blocked status
        if ($request->filled('blocked')) {
            $query->where('is_blocked', $request->blocked === 'blocked');
        }

        $apiKeys = $query->latest()->paginate(20)->withQueryString();
        $shops = VendorStore::orderBy('name')->get();

        return view('admin.pos-terminals.api-keys', compact('apiKeys', 'shops'));
    }

    /**
     * สร้าง API Key ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createApiKey(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:vendor_stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $apiKey = PosApiKey::create([
            'shop_id' => $validated['shop_id'],
            'key' => PosApiKey::generateKey(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'is_blocked' => false,
        ]);

        Log::info('Admin created API Key', [
            'api_key_id' => $apiKey->id,
            'shop_id' => $validated['shop_id'],
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', "สร้าง API Key สำเร็จ\n\nAPI Key: {$apiKey->key}\n\nโปรดคัดลอก API Key นี้แล้วส่งให้ลูกค้า");
    }

    /**
     * บล็อก API Key
     *
     * @param Request $request
     * @param PosApiKey $apiKey
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function blockApiKey(Request $request, PosApiKey $apiKey)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $apiKey->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $validated['reason'] ?? 'ถูกบล็อกโดย Admin',
            'blocked_by' => auth()->id(),
        ]);

        Log::info('Admin blocked API Key', [
            'api_key_id' => $apiKey->id,
            'reason' => $validated['reason'],
            'admin_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'บล็อก API Key สำเร็จ',
            ]);
        }

        return back()->with('success', 'บล็อก API Key สำเร็จ');
    }

    /**
     * ปลดบล็อก API Key
     *
     * @param PosApiKey $apiKey
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function unblockApiKey(Request $request, PosApiKey $apiKey)
    {
        $apiKey->update([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
            'blocked_by' => null,
        ]);

        Log::info('Admin unblocked API Key', [
            'api_key_id' => $apiKey->id,
            'admin_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ปลดบล็อก API Key สำเร็จ',
            ]);
        }

        return back()->with('success', 'ปลดบล็อก API Key สำเร็จ');
    }

    /**
     * บล็อก Terminal
     *
     * @param Request $request
     * @param PosTerminal $terminal
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function blockTerminal(Request $request, PosTerminal $terminal)
    {
        $terminal->block();

        Log::info('Admin blocked POS Terminal', [
            'terminal_id' => $terminal->id,
            'admin_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'บล็อก Terminal สำเร็จ',
            ]);
        }

        return back()->with('success', 'บล็อก POS Terminal สำเร็จ');
    }

    /**
     * ปลดบล็อก Terminal
     *
     * @param Request $request
     * @param PosTerminal $terminal
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function unblockTerminal(Request $request, PosTerminal $terminal)
    {
        $terminal->activate();

        Log::info('Admin unblocked POS Terminal', [
            'terminal_id' => $terminal->id,
            'admin_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ปลดบล็อก Terminal สำเร็จ',
            ]);
        }

        return back()->with('success', 'ปลดบล็อก POS Terminal สำเร็จ');
    }

    /**
     * ดึงสถานะ Online/Offline แบบ Real-time (สำหรับ AJAX polling)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOnlineStatus(Request $request): JsonResponse
    {
        $threshold = Carbon::now()->subMinutes(5);

        $terminals = PosTerminal::select('id', 'product_key', 'last_sync_at', 'status', 'is_verified')
            ->with(['apiKey:id,is_blocked'])
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'product_key' => $t->product_key,
                'status' => $t->status,
                'is_verified' => $t->is_verified,
                'is_online' => $t->last_sync_at && $t->last_sync_at >= $threshold,
                'last_sync' => $t->last_sync_at?->diffForHumans(),
                'api_key_blocked' => $t->apiKey?->is_blocked ?? false,
            ]);

        return response()->json([
            'success' => true,
            'terminals' => $terminals,
            'stats' => $this->getStats(),
            'checked_at' => now()->toISOString(),
        ]);
    }

    /**
     * Dashboard สำหรับดูภาพรวม POS
     *
     * @return View
     */
    public function dashboard(): View
    {
        $stats = $this->getStats();

        // Terminals ที่ Online
        $onlineTerminals = PosTerminal::with('shop')
            ->where('last_sync_at', '>=', Carbon::now()->subMinutes(5))
            ->where('status', PosTerminal::STATUS_ACTIVE)
            ->latest('last_sync_at')
            ->limit(10)
            ->get();

        // Terminals ที่รอ API Key
        $pendingTerminals = PosTerminal::with('shop')
            ->where('status', PosTerminal::STATUS_PENDING)
            ->orWhere('is_verified', false)
            ->latest()
            ->limit(10)
            ->get();

        // API Keys ที่ถูกบล็อก
        $blockedApiKeys = PosApiKey::with('shop')
            ->where('is_blocked', true)
            ->latest('blocked_at')
            ->limit(10)
            ->get();

        // รายงานยอดขายวันนี้
        $todaySales = DB::table('pos_daily_reports')
            ->whereDate('report_date', today())
            ->selectRaw('SUM(total_sales) as total, SUM(total_transactions) as transactions')
            ->first();

        return view('admin.pos-terminals.dashboard', compact(
            'stats',
            'onlineTerminals',
            'pendingTerminals',
            'blockedApiKeys',
            'todaySales'
        ));
    }

    /**
     * ดึงสถิติภาพรวม
     *
     * @return array
     */
    private function getStats(): array
    {
        $threshold = Carbon::now()->subMinutes(5);

        return [
            'total_terminals' => PosTerminal::count(),
            'active_terminals' => PosTerminal::where('status', PosTerminal::STATUS_ACTIVE)
                ->where('is_verified', true)
                ->count(),
            'online_terminals' => PosTerminal::where('last_sync_at', '>=', $threshold)->count(),
            'pending_terminals' => PosTerminal::where('is_verified', false)->count(),
            'blocked_terminals' => PosTerminal::where('status', PosTerminal::STATUS_BLOCKED)->count(),
            'total_api_keys' => PosApiKey::count(),
            'active_api_keys' => PosApiKey::where('is_active', true)
                ->where('is_blocked', false)
                ->count(),
            'blocked_api_keys' => PosApiKey::where('is_blocked', true)->count(),
        ];
    }
}
