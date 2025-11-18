<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreSchedule;
use App\Models\AICoreTenant;
use App\Models\AICoreFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI Core Schedule Controller
 *
 * จัดการ Schedules สำหรับเปิด/ปิด Features อัตโนมัติ
 */
class AICoreScheduleController extends Controller
{
    /**
     * แสดงรายการ Schedules ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $schedules = AICoreSchedule::with(['tenant', 'feature', 'creator'])
            ->latest()
            ->paginate(20);

        return view('admin.ai-core.schedules.index', compact('schedules'));
    }

    /**
     * แสดงฟอร์มสร้าง Schedule ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $features = AICoreFeature::where('is_enabled', true)->orderBy('feature_name')->get();
        $tenants = AICoreTenant::where('is_enabled', true)->orderBy('tenant_name')->get();
        $scheduleTypes = ['once', 'recurring', 'cron'];
        $actions = ['enable', 'disable', 'toggle'];
        $recurrences = ['daily', 'weekly', 'monthly', 'yearly'];

        return view('admin.ai-core.schedules.create', compact(
            'features',
            'tenants',
            'scheduleTypes',
            'actions',
            'recurrences'
        ));
    }

    /**
     * บันทึก Schedule ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'nullable|exists:ai_core_tenants,id',
            'feature_id' => 'required|exists:ai_core_features,id',
            'schedule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action' => 'required|in:enable,disable,toggle',
            'schedule_type' => 'required|in:once,recurring,cron',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'recurrence' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_time' => 'nullable|date_format:H:i',
            'cron_expression' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
            'max_executions' => 'nullable|integer|min:1',
            'notify_on_execution' => 'boolean',
            'notify_on_failure' => 'boolean',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $validated['is_enabled'] = true;
            $validated['status'] = 'pending';

            // คำนวณ next_execution_at
            if ($validated['schedule_type'] === 'once' && !empty($validated['starts_at'])) {
                $validated['next_execution_at'] = $validated['starts_at'];
            }

            $schedule = AICoreSchedule::create($validated);

            return redirect()
                ->route('admin.ai-core.schedules.show', $schedule)
                ->with('success', 'สร้าง Schedule สำเร็จ');
        } catch (\Exception $e) {
            Log::error('AI Core: สร้าง schedule ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียด Schedule
     *
     * @param AICoreSchedule $schedule
     * @return \Illuminate\View\View
     */
    public function show(AICoreSchedule $schedule)
    {
        $schedule->load(['tenant', 'feature', 'creator']);

        return view('admin.ai-core.schedules.show', compact('schedule'));
    }

    /**
     * แสดงฟอร์มแก้ไข Schedule
     *
     * @param AICoreSchedule $schedule
     * @return \Illuminate\View\View
     */
    public function edit(AICoreSchedule $schedule)
    {
        $features = AICoreFeature::where('is_enabled', true)->orderBy('feature_name')->get();
        $tenants = AICoreTenant::where('is_enabled', true)->orderBy('tenant_name')->get();
        $scheduleTypes = ['once', 'recurring', 'cron'];
        $actions = ['enable', 'disable', 'toggle'];
        $recurrences = ['daily', 'weekly', 'monthly', 'yearly'];

        return view('admin.ai-core.schedules.edit', compact(
            'schedule',
            'features',
            'tenants',
            'scheduleTypes',
            'actions',
            'recurrences'
        ));
    }

    /**
     * อัปเดต Schedule
     *
     * @param Request $request
     * @param AICoreSchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AICoreSchedule $schedule)
    {
        $validated = $request->validate([
            'tenant_id' => 'nullable|exists:ai_core_tenants,id',
            'feature_id' => 'required|exists:ai_core_features,id',
            'schedule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action' => 'required|in:enable,disable,toggle',
            'schedule_type' => 'required|in:once,recurring,cron',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'recurrence' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_time' => 'nullable|date_format:H:i',
            'cron_expression' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
            'max_executions' => 'nullable|integer|min:1',
            'notify_on_execution' => 'boolean',
            'notify_on_failure' => 'boolean',
        ]);

        try {
            $schedule->update($validated);

            return redirect()
                ->route('admin.ai-core.schedules.show', $schedule)
                ->with('success', 'อัปเดต Schedule สำเร็จ');
        } catch (\Exception $e) {
            Log::error('AI Core: อัปเดต schedule ล้มเหลว', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบ Schedule
     *
     * @param AICoreSchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AICoreSchedule $schedule)
    {
        try {
            $scheduleName = $schedule->schedule_name;
            $schedule->delete();

            return redirect()
                ->route('admin.ai-core.schedules.index')
                ->with('success', 'ลบ Schedule สำเร็จ: ' . $scheduleName);
        } catch (\Exception $e) {
            Log::error('AI Core: ลบ schedule ล้มเหลว', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เปิด/ปิดใช้งาน Schedule
     *
     * @param AICoreSchedule $schedule
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(AICoreSchedule $schedule)
    {
        try {
            if ($schedule->is_enabled) {
                $schedule->disable();
            } else {
                $schedule->enable();
            }

            return response()->json([
                'success' => true,
                'message' => $schedule->is_enabled ? 'เปิดใช้งาน Schedule แล้ว' : 'ปิดใช้งาน Schedule แล้ว',
                'is_enabled' => $schedule->is_enabled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute Schedule ทันที (สำหรับทดสอบ)
     *
     * @param AICoreSchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function execute(AICoreSchedule $schedule)
    {
        try {
            // TODO: Implement schedule execution logic
            // จะต้องเรียกใช้ service เพื่อ enable/disable feature ตาม action

            $schedule->recordExecution(true);

            return back()->with('success', 'Execute Schedule สำเร็จ');
        } catch (\Exception $e) {
            $schedule->recordExecution(false, $e->getMessage());

            Log::error('AI Core: Execute schedule ล้มเหลว', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
