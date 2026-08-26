<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneBanner;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * จัดการ Fortune Banner (DM image banners)
 *
 * รองรับ:
 *   - upload PNG/JPG/JPEG (ระบบ resize/compress อัตโนมัติเป็น 1280px JPEG)
 *   - เรียงลำดับด้วย sort_order
 *   - เปิด/ปิดเป็นรายตัว
 *   - ตั้งค่ากลยุทธ์การเลือก (random/rotation/sequential)
 */
class FortuneBannerController extends Controller
{
    /**
     * แสดงรายการแบนเนอร์
     */
    public function index()
    {
        $banners = FortuneBanner::ordered()->get();
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.banners.index', [
            'banners' => $banners,
            'settings' => $settings,
            'pageTitle' => 'จัดการแบนเนอร์ DM',
        ]);
    }

    /**
     * แสดงฟอร์ม upload แบนเนอร์ใหม่
     */
    public function create()
    {
        return view('admin.fortune.banners.create', [
            'pageTitle' => 'อัพโหลดแบนเนอร์ใหม่',
        ]);
    }

    /**
     * บันทึกแบนเนอร์ใหม่ (อัพโหลด + resize + JPEG compress)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image' => 'required|image|mimes:png,jpg,jpeg|max:10240', // 10 MB max
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $file = $request->file('image');

        // 🆕 (2026-05-07) เก็บที่ storage/app/public/fortune/banners/ — รอด deploy.sh git clean
        //   เดิม: public_path('images/fortune/banners') → ถูก git clean -fdx ลบทุก deploy
        //   ใหม่: Laravel Storage convention → deploy.sh excludes 'storage/app/public/*'
        $filename = 'banner-'.Str::random(8).'-'.time().'.jpg';
        $storagePath = 'fortune/banners/'.$filename;

        // Resize → temp file → ย้ายเข้า Storage::disk('public')
        $tmpPath = tempnam(sys_get_temp_dir(), 'fbanner_').'.jpg';
        $info = $this->resizeAndSaveJpeg($file->getRealPath(), $tmpPath, 1280, 85);

        if (! $info) {
            @unlink($tmpPath);

            return redirect()->back()
                ->withInput()
                ->with('error', 'ไม่สามารถประมวลผลรูปได้ กรุณาลองรูปอื่น');
        }

        Storage::disk('public')->put($storagePath, file_get_contents($tmpPath));
        @unlink($tmpPath);

        $banner = FortuneBanner::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image_path' => $storagePath,
            'width' => $info['width'],
            'height' => $info['height'],
            'file_size' => $info['size'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $validated['sort_order'] ?? (FortuneBanner::max('sort_order') + 1),
            'uploaded_by' => auth()->id(),
        ]);

        Log::info('Admin: upload fortune banner', [
            'banner_id' => $banner->id,
            'admin' => auth()->user()?->name,
            'size_kb' => round(($info['size'] ?? 0) / 1024),
        ]);

        return redirect()->route('admin.fortune.banners.index')
            ->with('success', '✅ อัพโหลดแบนเนอร์สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไข
     */
    public function edit(FortuneBanner $banner)
    {
        return view('admin.fortune.banners.edit', [
            'banner' => $banner,
            'pageTitle' => 'แก้ไขแบนเนอร์: '.$banner->name,
        ]);
    }

    /**
     * บันทึกการแก้ไข (รองรับเปลี่ยนรูปด้วย)
     */
    public function update(Request $request, FortuneBanner $banner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:10240',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? $banner->sort_order,
        ];

        // ถ้าอัพรูปใหม่ → resize + แทนที่ไฟล์เดิม
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'banner-'.Str::random(8).'-'.time().'.jpg';
            $storagePath = 'fortune/banners/'.$filename;

            $tmpPath = tempnam(sys_get_temp_dir(), 'fbanner_').'.jpg';
            $info = $this->resizeAndSaveJpeg($file->getRealPath(), $tmpPath, 1280, 85);

            if ($info) {
                Storage::disk('public')->put($storagePath, file_get_contents($tmpPath));
                @unlink($tmpPath);

                // ลบไฟล์เก่า — รองรับทั้ง legacy public_path และ new storage path
                $this->deleteOldBannerFile($banner->image_path);

                $updateData['image_path'] = $storagePath;
                $updateData['width'] = $info['width'];
                $updateData['height'] = $info['height'];
                $updateData['file_size'] = $info['size'];
            } else {
                @unlink($tmpPath);
            }
        }

        $banner->update($updateData);

        return redirect()->route('admin.fortune.banners.index')
            ->with('success', '✅ บันทึกการแก้ไขสำเร็จ');
    }

    /**
     * ลบแบนเนอร์ (soft delete) + เก็บไฟล์ไว้
     */
    public function destroy(FortuneBanner $banner)
    {
        $banner->delete();

        return redirect()->route('admin.fortune.banners.index')
            ->with('success', '✅ ลบแบนเนอร์สำเร็จ');
    }

    /**
     * เปิด/ปิด banner เป็นรายตัว (toggle ผ่าน AJAX)
     */
    public function toggle(FortuneBanner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $banner->is_active,
        ]);
    }

    /**
     * บันทึกลำดับใหม่ (drag & drop)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:fortune_banners,id',
        ]);

        foreach ($validated['order'] as $index => $bannerId) {
            FortuneBanner::where('id', $bannerId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * บันทึกการตั้งค่า banner (ใน fortune_telling_settings)
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'enable_dm_banner' => 'nullable|boolean',
            'banner_pick_strategy' => 'required|in:random,rotation,sequential',
            'banner_send_on_reaction' => 'nullable|boolean',
            'banner_send_on_comment' => 'nullable|boolean',
            'banner_send_on_welcome' => 'nullable|boolean',
            // 🃏 (2026-08-26) การ์ดทางเข้า — อยู่ในฟอร์มเดียวกันเพราะคุมเรื่องเดียวกัน (หน้าตา DM ขาออก)
            'entry_cards_on_dm' => 'nullable|boolean',
            'birth_day_cards_enabled' => 'nullable|boolean',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'enable_dm_banner' => (bool) ($validated['enable_dm_banner'] ?? false),
            'banner_pick_strategy' => $validated['banner_pick_strategy'],
            'banner_send_on_reaction' => (bool) ($validated['banner_send_on_reaction'] ?? false),
            'banner_send_on_comment' => (bool) ($validated['banner_send_on_comment'] ?? false),
            'banner_send_on_welcome' => (bool) ($validated['banner_send_on_welcome'] ?? false),
            'entry_cards_on_dm' => (bool) ($validated['entry_cards_on_dm'] ?? false),
            'birth_day_cards_enabled' => (bool) ($validated['birth_day_cards_enabled'] ?? false),
        ]);

        // ล้าง static memo ของโปรเซสนี้ ให้หน้าที่ redirect กลับไปโชว์ค่าที่เพิ่งบันทึกทันที
        // (ฝั่ง queue worker ไม่ต้องทำอะไร — memo มี TTL 5 วิ อยู่แล้ว จึงไม่ต้อง restart worker)
        FortuneTellingSetting::clearSettingsCache();

        return redirect()->route('admin.fortune.banners.index')
            ->with('success', '✅ บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * ลบไฟล์ banner เก่า — รองรับทั้ง legacy (`images/...` ที่ public_path) และ new (`fortune/...` ที่ Storage::disk('public'))
     *
     * เก่า legacy: 'images/fortune/banners/banner-X.jpg' → public_path
     * ใหม่ storage: 'fortune/banners/banner-X.jpg' → Storage::disk('public')
     */
    protected function deleteOldBannerFile(?string $oldPath): void
    {
        if (empty($oldPath)) {
            return;
        }

        // Legacy: เคยเก็บที่ public_path('images/fortune/banners/...')
        if (str_starts_with($oldPath, 'images/')) {
            $legacyFull = public_path($oldPath);
            $legacyDir = public_path('images/fortune/banners');
            if (file_exists($legacyFull) && str_starts_with((string) realpath($legacyFull), (string) realpath($legacyDir))) {
                @unlink($legacyFull);
            }

            return;
        }

        // New: storage/app/public/fortune/banners/...
        Storage::disk('public')->delete($oldPath);
    }

    /**
     * Resize + แปลงเป็น JPEG quality 85, max width = $maxWidth, รักษาอัตราส่วน
     *
     * @return array|null ['width', 'height', 'size'] หรือ null ถ้าล้มเหลว
     */
    protected function resizeAndSaveJpeg(string $sourcePath, string $destPath, int $maxWidth, int $quality): ?array
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }

        [$srcW, $srcH, $type] = $info;

        // โหลดรูปต้นฉบับตาม type
        $srcImg = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            default => null,
        };

        if (! $srcImg) {
            return null;
        }

        // คำนวณขนาดใหม่ (รักษา aspect ratio, ไม่ขยายให้ใหญ่ขึ้น)
        if ($srcW <= $maxWidth) {
            $newW = $srcW;
            $newH = $srcH;
        } else {
            $newW = $maxWidth;
            $newH = (int) round($maxWidth * $srcH / $srcW);
        }

        // สร้าง canvas ใหม่
        $newImg = imagecreatetruecolor($newW, $newH);

        // PNG transparency → fill ด้วยขาว (เพราะแปลงเป็น JPEG ไม่มี alpha)
        $white = imagecolorallocate($newImg, 255, 255, 255);
        imagefilledrectangle($newImg, 0, 0, $newW, $newH, $white);

        // resize ด้วย bicubic (high quality)
        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        // บันทึก JPEG
        $saved = imagejpeg($newImg, $destPath, $quality);

        imagedestroy($srcImg);
        imagedestroy($newImg);

        if (! $saved) {
            return null;
        }

        return [
            'width' => $newW,
            'height' => $newH,
            'size' => @filesize($destPath) ?: 0,
        ];
    }
}
