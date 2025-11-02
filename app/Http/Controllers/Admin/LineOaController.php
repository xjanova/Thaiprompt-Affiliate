<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineOaSetting;
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
            'channel_id' => ['nullable', 'string', 'max:255'],
            'channel_secret' => ['nullable', 'string', 'max:255'],
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
}
