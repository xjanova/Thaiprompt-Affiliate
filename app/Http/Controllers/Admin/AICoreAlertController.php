<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreAlert;
use App\Models\AICoreFeature;
use App\Models\AICoreTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI Core Alert Controller
 *
 * จัดการ Alerts และการแจ้งเตือนในระบบ AI Core
 */
class AICoreAlertController extends Controller
{
    /**
     * แสดงรายการ Alerts ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = AICoreAlert::with(['tenant', 'feature']);

        // กรองตาม severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // กรองตาม alert_type
        if ($request->filled('type')) {
            $query->where('alert_type', $request->type);
        }

        // กรองตาม status (read/unread)
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }

        // กรองตาม tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // กรองตาม feature
        if ($request->filled('feature_id')) {
            $query->where('feature_id', $request->feature_id);
        }

        // เรียงตาม severity และวันที่
        $alerts = $query->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // ดึงข้อมูลสำหรับ filters
        $severities = ['info', 'warning', 'error', 'critical'];
        $alertTypes = AICoreAlert::distinct('alert_type')->pluck('alert_type');
        $tenants = AICoreTenant::where('is_enabled', true)->orderBy('tenant_name')->get();
        $features = AICoreFeature::where('is_enabled', true)->orderBy('feature_name')->get();

        // นับจำนวน alerts แต่ละประเภท
        $stats = [
            'total' => AICoreAlert::count(),
            'unread' => AICoreAlert::unread()->count(),
            'critical' => AICoreAlert::unread()->where('severity', 'critical')->count(),
            'error' => AICoreAlert::unread()->where('severity', 'error')->count(),
            'warning' => AICoreAlert::unread()->where('severity', 'warning')->count(),
        ];

        return view('admin.ai-core.alerts.index', compact(
            'alerts',
            'severities',
            'alertTypes',
            'tenants',
            'features',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียด Alert
     *
     * @param AICoreAlert $alert
     * @return \Illuminate\View\View
     */
    public function show(AICoreAlert $alert)
    {
        // โหลดข้อมูลที่เกี่ยวข้อง
        $alert->load(['tenant', 'feature', 'acknowledgedByUser', 'resolvedByUser']);

        // ทำเครื่องหมายว่าอ่านแล้ว (ถ้ายังไม่ได้อ่าน)
        if (!$alert->is_read) {
            $alert->markAsRead();
        }

        return view('admin.ai-core.alerts.show', compact('alert'));
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @param AICoreAlert $alert
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function markAsRead(AICoreAlert $alert)
    {
        try {
            $alert->markAsRead();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ทำเครื่องหมายว่าอ่านแล้ว',
                ]);
            }

            return back()->with('success', 'ทำเครื่องหมายว่าอ่านแล้ว');
        } catch (\Exception $e) {
            Log::error('AI Core: ทำเครื่องหมายอ่านล้มเหลว', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้วทั้งหมด
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        try {
            $count = AICoreAlert::unread()->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return back()->with('success', "ทำเครื่องหมายว่าอ่านแล้ว {$count} รายการ");
        } catch (\Exception $e) {
            Log::error('AI Core: ทำเครื่องหมายอ่านทั้งหมดล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * รับทราบ Alert (Acknowledge)
     *
     * @param Request $request
     * @param AICoreAlert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acknowledge(Request $request, AICoreAlert $alert)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $alert->acknowledge($validated['notes'] ?? null);

            return back()->with('success', 'รับทราบ Alert แล้ว');
        } catch (\Exception $e) {
            Log::error('AI Core: รับทราบ alert ล้มเหลว', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แก้ไข Alert (Resolve)
     *
     * @param Request $request
     * @param AICoreAlert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolve(Request $request, AICoreAlert $alert)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $alert->resolve($validated['resolution_notes'] ?? null);

            return back()->with('success', 'แก้ไข Alert สำเร็จ');
        } catch (\Exception $e) {
            Log::error('AI Core: แก้ไข alert ล้มเหลว', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปิด Alert (Dismiss)
     *
     * @param AICoreAlert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dismiss(AICoreAlert $alert)
    {
        try {
            $alert->dismiss();

            return back()->with('success', 'ปิด Alert แล้ว');
        } catch (\Exception $e) {
            Log::error('AI Core: ปิด alert ล้มเหลว', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบ Alert
     *
     * @param AICoreAlert $alert
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AICoreAlert $alert)
    {
        try {
            $alertTitle = $alert->title;
            $alert->delete();

            return redirect()
                ->route('admin.ai-core.alerts.index')
                ->with('success', 'ลบ Alert สำเร็จ: ' . $alertTitle);
        } catch (\Exception $e) {
            Log::error('AI Core: ลบ alert ล้มเหลว', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบ Alerts ที่หมดอายุทั้งหมด
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteExpired()
    {
        try {
            $count = AICoreAlert::where('expires_at', '<=', now())
                ->where('auto_dismiss', true)
                ->delete();

            return back()->with('success', "ลบ Alert ที่หมดอายุ {$count} รายการ");
        } catch (\Exception $e) {
            Log::error('AI Core: ลบ alerts ที่หมดอายุล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
