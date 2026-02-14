<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdCardSetting;
use App\Models\Rank;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin ID Card Controller
 *
 * จัดการ Virtual ID Card สำหรับผู้ดูแลระบบ:
 * - ออกแบบบัตรด้วย drag & drop
 * - ตั้งค่าพื้นหลัง (สี, gradient, รูปภาพ)
 * - ดูตัวอย่างแบบ realtime
 * - ดูบัตรของสมาชิกแต่ละคน
 */
class IdCardController extends Controller
{
    /**
     * แสดงหน้ารายการการตั้งค่า ID Card
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = IdCardSetting::with([])
            ->orderBy('rank_level')
            ->orderBy('is_default', 'desc')
            ->get();

        $ranks = Rank::active()->byLevel()->get();

        return view('admin.id-card.index', compact('settings', 'ranks'));
    }

    /**
     * แสดงหน้า Designer สำหรับออกแบบ ID Card
     *
     * @return \Illuminate\View\View
     */
    public function designer(Request $request)
    {
        $rankLevel = $request->get('rank_level', 1);

        // ดึง setting สำหรับ rank นี้ หรือสร้างใหม่
        $setting = IdCardSetting::where('rank_level', $rankLevel)->first();

        if (! $setting) {
            // ใช้ default setting
            $setting = IdCardSetting::where('is_default', true)->first();

            // ถ้ายังไม่มี default ให้สร้างค่าเริ่มต้น
            if (! $setting) {
                $setting = new IdCardSetting([
                    'rank_level' => $rankLevel,
                    'name' => 'New Setting',
                    'background_type' => 'gradient',
                    'background_gradient' => IdCardSetting::getDefaultGradientForRank($rankLevel),
                ]);
            }
        }

        $ranks = Rank::active()->byLevel()->get();
        $currentRank = Rank::where('level', $rankLevel)->first();

        // ดึง user ตัวอย่างสำหรับ preview
        $sampleUser = User::where('current_rank_id', $currentRank?->id)->first()
            ?? User::first();

        return view('admin.id-card.designer', compact(
            'setting',
            'ranks',
            'currentRank',
            'sampleUser',
            'rankLevel'
        ));
    }

    /**
     * บันทึกการตั้งค่า ID Card
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rank_level' => 'nullable|integer|min:1|max:8',
            'name' => 'required|string|max:100',
            'name_th' => 'nullable|string|max:100',
            'background_type' => 'required|in:gradient,image,color',
            'background_color' => 'nullable|string|max:7',
            'background_gradient' => 'nullable|array',
            'background_image' => 'nullable|string|max:500',
            'overlay_color' => 'nullable|string|max:50',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'logo_position' => 'nullable|array',
            'profile_position' => 'nullable|array',
            'name_position' => 'nullable|array',
            'rank_badge_position' => 'nullable|array',
            'member_number_position' => 'nullable|array',
            'qr_position' => 'nullable|array',
            'member_since_position' => 'nullable|array',
            'stars_position' => 'nullable|array',
            'border_style' => 'nullable|array',
            'shadow_style' => 'nullable|array',
            'effects' => 'nullable|array',
            'custom_css' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // หาหรือสร้าง setting
        if ($data['rank_level']) {
            $setting = IdCardSetting::firstOrNew(['rank_level' => $data['rank_level']]);
        } else {
            $setting = IdCardSetting::firstOrNew(['rank_level' => null, 'is_default' => true]);
            $data['is_default'] = true;
        }

        $setting->fill($data);
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสำเร็จ',
            'data' => $setting,
        ]);
    }

    /**
     * อัพโหลดภาพพื้นหลัง
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadBackground(Request $request, ImageUploadService $imageUploadService)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'รูปภาพไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // อัพโหลดรูปภาพ (แปลงเป็น WebP)
            $path = $imageUploadService->uploadImage(
                $request->file('image'),
                'id-card-backgrounds',
                900, // max width
                560, // max height (ตามสัดส่วนบัตร)
                85   // quality
            );

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดสำเร็จ',
                'path' => $path,
                'url' => asset('storage/'.$path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพโหลด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดูบัตรของสมาชิกที่ระบุ
     *
     * @return \Illuminate\View\View
     */
    public function viewUserCard(User $user)
    {
        $user->load('currentRank');
        $currentRank = $user->currentRank;

        // ดึงการตั้งค่า ID Card
        $idCardSettings = IdCardSetting::getActiveSettingsForRank($currentRank?->level ?? 1);

        return view('admin.id-card.view-user-card', compact(
            'user',
            'currentRank',
            'idCardSettings'
        ));
    }

    /**
     * ดึง preview data สำหรับ real-time preview
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewData(Request $request)
    {
        $rankLevel = $request->get('rank_level', 1);
        $userId = $request->get('user_id');

        // ดึง user สำหรับ preview
        if ($userId) {
            $user = User::find($userId);
        } else {
            $rank = Rank::where('level', $rankLevel)->first();
            $user = User::where('current_rank_id', $rank?->id)->first()
                ?? User::first();
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้สำหรับ preview',
            ], 404);
        }

        $currentRank = $user->currentRank ?? Rank::where('level', $rankLevel)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'member_number' => $user->member_number ?? 'TP'.str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'profile_picture_url' => $user->profile_picture_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random&size=200',
                    'created_at' => $user->created_at->format('M Y'),
                    'rank_points' => $user->rank_points ?? 0,
                ],
                'rank' => $currentRank ? [
                    'name' => $currentRank->name,
                    'name_th' => $currentRank->name_th,
                    'level' => $currentRank->level,
                    'badge_icon' => $currentRank->badge_icon ?? '🥉',
                    'stars' => $currentRank->stars ?? 1,
                    'color' => $currentRank->color,
                ] : null,
            ],
        ]);
    }

    /**
     * ลบการตั้งค่า ID Card
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(IdCardSetting $setting)
    {
        // ไม่ลบ default setting
        if ($setting->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบการตั้งค่าเริ่มต้นได้',
            ], 403);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการตั้งค่าสำเร็จ',
        ]);
    }

    /**
     * คัดลอกการตั้งค่าไปยัง rank อื่น
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicate(Request $request, IdCardSetting $setting)
    {
        $validator = Validator::make($request->all(), [
            'target_rank_level' => 'required|integer|min:1|max:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetRankLevel = $request->input('target_rank_level');

        // สร้างสำเนา
        $newSetting = $setting->replicate();
        $newSetting->rank_level = $targetRankLevel;
        $newSetting->name = $setting->name.' (Copy)';
        $newSetting->is_default = false;

        // ปรับ gradient ให้เหมาะกับ rank ใหม่
        $newSetting->background_gradient = IdCardSetting::getDefaultGradientForRank($targetRankLevel);

        $newSetting->save();

        return response()->json([
            'success' => true,
            'message' => 'คัดลอกการตั้งค่าสำเร็จ',
            'data' => $newSetting,
        ]);
    }
}
