<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineOaSetting;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\Request;

class LineOaController extends Controller
{
    /**
     * Show LINE OA settings page
     */
    public function index()
    {
        $settings = LineOaSetting::first() ?? new LineOaSetting();

        return view('admin.line-oa.index', compact('settings'));
    }

    /**
     * Update LINE OA settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'login_channel_id' => ['nullable', 'string', 'max:255'],
            'channel_secret' => ['nullable', 'string', 'max:255'],
            'redirect_uri' => ['nullable', 'url', 'max:500'],
            'messaging_channel_id' => ['nullable', 'string', 'max:255'],
            'channel_access_token' => ['nullable', 'string'],
            'liff_id' => ['nullable', 'string', 'max:255'],
            'require_line_registration' => ['boolean'],
            'enable_line_messaging' => ['boolean'],
            'welcome_message' => ['nullable', 'string'],
            'registration_success_message' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $settings = LineOaSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = LineOaSetting::create($validated);
        }

        // Clear cache
        LineOaSetting::clearCache();

        return redirect()->route('admin.line-oa.index')
            ->with('success', 'บันทึกการตั้งค่า LINE OA เรียบร้อยแล้ว');
    }

    /**
     * Test LINE messaging
     */
    public function testMessage(Request $request)
    {
        $request->validate([
            'line_user_id' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $lineService = new LineService();

        if (!$lineService->isConfigured()) {
            return back()->with('error', 'LINE OA ยังไม่ได้ตั้งค่า');
        }

        $success = $lineService->sendPushMessage(
            $request->line_user_id,
            $request->message
        );

        if ($success) {
            return back()->with('success', 'ส่งข้อความทดสอบสำเร็จ');
        }

        return back()->with('error', 'ไม่สามารถส่งข้อความได้ กรุณาตรวจสอบ Channel Access Token');
    }

    /**
     * Show LINE login logs
     */
    public function logs(Request $request)
    {
        $query = \App\Models\LineLoginLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('line_user_id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $logs = $query->paginate(50);

        return view('admin.line-oa.logs', compact('logs'));
    }

    /**
     * Test LINE API connection
     */
    public function testConnection()
    {
        $lineService = new LineService();
        $results = $lineService->testConnection();

        return response()->json($results);
    }

    /**
     * Quick update LINE OA settings (สำหรับ Quick Settings Panel)
     *
     * รับ PATCH request จาก Quick Settings Panel เพื่ออัพเดทการตั้งค่าแบบรวดเร็ว
     * รองรับการอัพเดทเฉพาะ field ที่ส่งมา
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickUpdate(Request $request)
    {
        // ตรวจสอบข้อมูลที่ส่งมา (อนุญาตเฉพาะ boolean fields)
        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'enable_line_messaging' => ['sometimes', 'boolean'],
            'require_line_registration' => ['sometimes', 'boolean'],
        ]);

        try {
            // ดึงการตั้งค่าปัจจุบัน หรือสร้างใหม่ถ้ายังไม่มี
            $settings = LineOaSetting::first();

            if (!$settings) {
                $settings = LineOaSetting::create(array_merge([
                    'is_active' => false,
                    'enable_line_messaging' => false,
                    'require_line_registration' => false,
                ], $validated));
            } else {
                // อัพเดทเฉพาะ fields ที่ส่งมา
                $settings->update($validated);
            }

            // ล้าง cache
            LineOaSetting::clearCache();

            // สร้างข้อความแจ้งเตือนตามการเปลี่ยนแปลง
            $message = $this->generateQuickUpdateMessage($validated);

            return response()->json([
                'success' => true,
                'message' => $message,
                'settings' => [
                    'is_active' => $settings->is_active,
                    'enable_line_messaging' => $settings->enable_line_messaging,
                    'require_line_registration' => $settings->require_line_registration,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('LINE Quick Update Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทการตั้งค่าได้: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้างข้อความแจ้งเตือนตามการเปลี่ยนแปลง
     *
     * @param array $changes
     * @return string
     */
    private function generateQuickUpdateMessage(array $changes): string
    {
        $messages = [];

        if (isset($changes['is_active'])) {
            $messages[] = $changes['is_active']
                ? '🟢 เปิดใช้งานระบบ LINE OA'
                : '⚫ ปิดใช้งานระบบ LINE OA';
        }

        if (isset($changes['enable_line_messaging'])) {
            $messages[] = $changes['enable_line_messaging']
                ? '💬 เปิดใช้งาน LINE Messaging'
                : '🔇 ปิดใช้งาน LINE Messaging';
        }

        if (isset($changes['require_line_registration'])) {
            $messages[] = $changes['require_line_registration']
                ? '✅ บังคับลงทะเบียนผ่าน LINE'
                : '⭕ ไม่บังคับลงทะเบียนผ่าน LINE';
        }

        return implode(' | ', $messages) ?: 'อัพเดทสำเร็จ';
    }

    /**
     * Get users with LINE User ID
     */
    public function getLineUsers(Request $request)
    {
        $query = User::whereNotNull('line_user_id')
            ->where('line_user_id', '!=', '')
            ->select('id', 'name', 'email', 'line_user_id', 'line_display_name', 'line_verified')
            ->orderBy('name', 'asc');

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('line_display_name', 'like', "%{$search}%")
                  ->orWhere('line_user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
