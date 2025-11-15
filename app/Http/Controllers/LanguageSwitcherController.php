<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

/**
 * LanguageSwitcherController
 *
 * จัดการการเปลี่ยนภาษาของระบบ
 */
class LanguageSwitcherController extends Controller
{
    /**
     * รายการภาษาที่รองรับ
     */
    protected const SUPPORTED_LANGUAGES = [
        'th', 'en', 'zh-CN', 'zh-TW', 'ja', 'ko',
        'vi', 'id', 'ms', 'es', 'fr', 'de', 'ru', 'ar',
    ];

    /**
     * เปลี่ยนภาษา
     *
     * @param Request $request
     * @param string $lang ISO language code
     * @return RedirectResponse
     */
    public function switch(Request $request, string $lang): RedirectResponse
    {
        // ตรวจสอบว่าภาษารองรับหรือไม่
        if (!in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return redirect()->back()->with('error', 'ภาษาไม่รองรับ');
        }

        // บันทึกภาษาใน session
        Session::put('locale', $lang);

        // บันทึกภาษาใน cookie (30 วัน)
        cookie()->queue('locale', $lang, 60 * 24 * 30);

        // ถ้า user login อยู่ ให้บันทึกใน database
        if (auth()->check()) {
            $user = auth()->user();
            $user->preferred_language = $lang;
            $user->save();
        }

        return redirect()->back()->with('success', 'เปลี่ยนภาษาเรียบร้อยแล้ว');
    }

    /**
     * ดึงภาษาปัจจุบัน
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Request $request): \Illuminate\Http\JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'current' => $locale,
            'supported' => self::SUPPORTED_LANGUAGES,
        ]);
    }
}
