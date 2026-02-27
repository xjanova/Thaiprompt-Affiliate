<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * อัพโหลดไฟล์เสียง (เพลง/เอฟเฟค) สำหรับเกม
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAudio(Request $request)
    {
        try {
            $request->validate([
                'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240', // จำกัด 10MB
                'setting_key' => 'required|string',
                'track_name' => 'required|string|max:100',
            ]);

            $settingKey = $request->input('setting_key');
            $trackName = $request->input('track_name');

            // ตรวจสอบว่า setting มีอยู่จริง
            $setting = GameSetting::where('key', $settingKey)
                ->where('type', 'json')
                ->first();

            if (! $setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการตั้งค่า: '.$settingKey,
                ], 404);
            }

            // สร้าง directory ถ้ายังไม่มี
            $uploadDir = 'public/audio/games/snake-io';
            if (! Storage::exists($uploadDir)) {
                Storage::makeDirectory($uploadDir);
            }

            // อัพโหลดไฟล์
            $file = $request->file('audio_file');
            $fileName = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $path = $file->storeAs($uploadDir, $fileName);

            // สร้าง public URL
            $publicUrl = '/storage/audio/games/snake-io/'.$fileName;

            // อัพเดท JSON tracks ใน setting
            $tracks = json_decode($setting->value, true) ?? [];
            $tracks[] = [
                'name' => $trackName,
                'url' => $publicUrl,
            ];

            // บันทึก setting
            GameSetting::set($settingKey, $tracks, 'json', $setting->group);
            GameSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดไฟล์เสียงสำเร็จ',
                'track' => [
                    'name' => $trackName,
                    'url' => $publicUrl,
                ],
                'tracks' => $tracks,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง: '.implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบไฟล์เสียงจาก track list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAudio(Request $request)
    {
        try {
            $request->validate([
                'setting_key' => 'required|string',
                'track_index' => 'required|integer|min:0',
            ]);

            $settingKey = $request->input('setting_key');
            $trackIndex = $request->input('track_index');

            // ตรวจสอบว่า setting มีอยู่จริง
            $setting = GameSetting::where('key', $settingKey)
                ->where('type', 'json')
                ->first();

            if (! $setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการตั้งค่า: '.$settingKey,
                ], 404);
            }

            // ดึง tracks ปัจจุบัน
            $tracks = json_decode($setting->value, true) ?? [];

            if (! isset($tracks[$trackIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ track ที่ index: '.$trackIndex,
                ], 404);
            }

            // ลบไฟล์จาก storage (ถ้าเป็นไฟล์ที่อัพโหลดเอง)
            $trackUrl = $tracks[$trackIndex]['url'] ?? '';
            if (str_starts_with($trackUrl, '/storage/audio/')) {
                $storagePath = str_replace('/storage/', 'public/', $trackUrl);
                if (Storage::exists($storagePath)) {
                    Storage::delete($storagePath);
                }
            }

            // ลบ track จาก array
            $deletedTrack = $tracks[$trackIndex];
            array_splice($tracks, $trackIndex, 1);

            // บันทึก setting
            GameSetting::set($settingKey, $tracks, 'json', $setting->group);
            GameSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ลบเพลง "'.$deletedTrack['name'].'" สำเร็จ',
                'tracks' => array_values($tracks),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed ข้อมูล Music Settings เริ่มต้น (ถ้ายังไม่มี)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function seedMusic()
    {
        try {
            // ตรวจสอบว่ามี music settings อยู่แล้วหรือยัง
            $existingCount = GameSetting::where('group', 'snake_io_music')->count();

            if ($existingCount > 0) {
                return redirect()
                    ->route('admin.games.game-settings.index')
                    ->with('success', 'มี Music Settings อยู่แล้ว ('.$existingCount.' รายการ)');
            }

            // สร้าง music settings เริ่มต้น
            $musicSettings = [
                [
                    'key' => 'music_enabled',
                    'value' => 'true',
                    'type' => 'boolean',
                    'group' => 'snake_io_music',
                    'description' => 'เปิด/ปิดระบบเพลงในเกม',
                    'is_active' => true,
                ],
                [
                    'key' => 'music_title_tracks',
                    'value' => json_encode([]),
                    'type' => 'json',
                    'group' => 'snake_io_music',
                    'description' => 'รายการเพลงสำหรับหน้า Title Screen (JSON array: [{name, url}])',
                    'is_active' => true,
                ],
                [
                    'key' => 'music_gameplay_tracks',
                    'value' => json_encode([]),
                    'type' => 'json',
                    'group' => 'snake_io_music',
                    'description' => 'รายการเพลงสำหรับระหว่างเล่น (JSON array: [{name, url}])',
                    'is_active' => true,
                ],
                [
                    'key' => 'music_default_volume',
                    'value' => '0.5',
                    'type' => 'float',
                    'group' => 'snake_io_music',
                    'description' => 'ระดับเสียงเพลงเริ่มต้น (0.0 - 1.0)',
                    'is_active' => true,
                ],
                [
                    'key' => 'sfx_enabled',
                    'value' => 'true',
                    'type' => 'boolean',
                    'group' => 'snake_io_music',
                    'description' => 'เปิด/ปิดเอฟเฟกต์เสียง (เก็บไอเท็ม, ตาย, ฯลฯ)',
                    'is_active' => true,
                ],
                [
                    'key' => 'sfx_default_volume',
                    'value' => '0.7',
                    'type' => 'float',
                    'group' => 'snake_io_music',
                    'description' => 'ระดับเสียงเอฟเฟกต์เริ่มต้น (0.0 - 1.0)',
                    'is_active' => true,
                ],
            ];

            foreach ($musicSettings as $data) {
                GameSetting::updateOrCreate(
                    ['key' => $data['key']],
                    $data
                );
            }

            GameSetting::clearCache();

            return redirect()
                ->route('admin.games.game-settings.index')
                ->with('success', 'สร้าง Music Settings เริ่มต้นสำเร็จ! (6 รายการ)');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.games.game-settings.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }
}
