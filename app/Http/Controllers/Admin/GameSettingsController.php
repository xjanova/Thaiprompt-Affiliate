<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSetting;
use Illuminate\Http\Request;

/**
 * จัดการหน้าตั้งค่าเกมในระบบ Admin
 */
class GameSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่าเกม
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึง settings ทั้งหมด จัดกลุ่มตาม group
        $settings = GameSetting::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return view('admin.game-settings.index', [
            'settings' => $settings,
            'pageTitle' => 'ตั้งค่าเกม',
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
        try {
            // ดึงข้อมูลทั้งหมดจาก request
            $data = $request->except('_token', '_method');

            foreach ($data as $key => $value) {
                // ดึง setting เดิม
                $setting = GameSetting::where('key', $key)->first();

                if ($setting) {
                    // อัพเดทค่า
                    GameSetting::set($key, $value, $setting->type, $setting->group);
                }
            }

            // ล้าง cache ทั้งหมด
            GameSetting::clearCache();

            return redirect()
                ->route('admin.games.game-settings.index')
                ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.games.game-settings.index')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
