<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineSignupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * จัดการการตั้งค่าระบบสมัครสมาชิกผ่าน LINE
 */
class LineSignupSettingsController extends Controller
{
    /**
     * แสดงหน้า Settings Dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงการตั้งค่าทั้งหมด จัดกลุ่มตาม group
        $settings = LineSignupSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        // สถิติเบื้องต้น
        $stats = $this->getStats();

        return view('admin.line-membership-signup.settings', [
            'settings' => $settings,
            'stats' => $stats,
            'pageTitle' => 'ตั้งค่าระบบสมัครสมาชิก LINE',
        ]);
    }

    /**
     * อัพเดทการตั้งค่า
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Validate
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $updatedCount = 0;

        foreach ($request->input('settings', []) as $key => $value) {
            $setting = LineSignupSetting::where('key', $key)->first();

            if (!$setting) {
                continue;
            }

            // ตรวจสอบว่าแก้ไขได้หรือไม่
            if (!$setting->is_editable) {
                continue;
            }

            // แปลงค่าตามประเภท
            $castValue = $this->castValueByType($value, $setting->type);

            // อัพเดท
            LineSignupSetting::set(
                $key,
                $castValue,
                $setting->type,
                $setting->group,
                $setting->description
            );

            $updatedCount++;
        }

        // Clear cache
        LineSignupSetting::clearCache();

        return redirect()
            ->route('admin.line-membership-signup.settings')
            ->with('success', "อัพเดทการตั้งค่าสำเร็จ {$updatedCount} รายการ");
    }

    /**
     * รีเซ็ตการตั้งค่ากลับเป็นค่าเริ่มต้น
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $group = $request->input('group');

        if ($group && in_array($group, [
            LineSignupSetting::GROUP_SIGNUP,
            LineSignupSetting::GROUP_OTP,
            LineSignupSetting::GROUP_VALIDATION,
            LineSignupSetting::GROUP_REWARDS,
            LineSignupSetting::GROUP_GAMIFICATION,
            LineSignupSetting::GROUP_SESSION,
        ])) {
            // รีเซ็ตเฉพาะกลุ่ม
            LineSignupSetting::where('group', $group)->delete();
            $message = "รีเซ็ตการตั้งค่ากลุ่ม {$group} สำเร็จ";
        } else {
            // รีเซ็ตทั้งหมด (ยกเว้น integration)
            LineSignupSetting::where('group', '!=', LineSignupSetting::GROUP_INTEGRATION)->delete();
            $message = 'รีเซ็ตการตั้งค่าทั้งหมดสำเร็จ';
        }

        // Clear cache
        LineSignupSetting::clearCache();

        // Re-seed
        \Artisan::call('db:seed', [
            '--class' => 'LineSignupSettingSeeder',
            '--force' => true,
        ]);

        return redirect()
            ->route('admin.line-membership-signup.settings')
            ->with('success', $message);
    }

    /**
     * ทดสอบการเชื่อมต่อ LINE
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection()
    {
        $accessToken = LineSignupSetting::get('integration.line_channel_access_token');

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Access Token กรุณาตั้งค่าก่อน',
            ], 400);
        }

        // ทดสอบการเชื่อมต่อโดยเรียก LINE API
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.line.me/v2/bot/info', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อ LINE สำเร็จ',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เชื่อมต่อ LINE ล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงสถิติเบื้องต้น
     *
     * @return array
     */
    protected function getStats(): array
    {
        // ใช้ Model ที่มีอยู่
        $totalSessions = \App\Models\LineSignupSession::count();
        $completedSessions = \App\Models\LineSignupSession::where('status', 'completed')->count();
        $activeSessions = \App\Models\LineSignupSession::where('status', 'active')->count();
        $abandonedSessions = \App\Models\LineSignupSession::where('status', 'abandoned')->count();

        // คำนวณ conversion rate
        $conversionRate = $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0;

        return [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'active_sessions' => $activeSessions,
            'abandoned_sessions' => $abandonedSessions,
            'conversion_rate' => round($conversionRate, 2),
        ];
    }

    /**
     * แปลงค่าตามประเภทข้อมูล
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function castValueByType($value, string $type)
    {
        return match ($type) {
            LineSignupSetting::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            LineSignupSetting::TYPE_INTEGER => (int) $value,
            LineSignupSetting::TYPE_JSON => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }
}
