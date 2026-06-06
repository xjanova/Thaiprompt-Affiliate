<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneConsentImage;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * จัดการ "กติกาก่อนจองคิว" (Consent Gate)
 *
 * - ข้อความกติกา (แสดงก่อนสร้างบิล) + ข้อความเตือนตอนยกเลิก — แอดมินแก้ได้
 * - คลังรูปเตือน: อัปโหลด PNG/JPG (resize 1280px JPEG), เปิด/ปิด, จัดเรียง, สุ่ม
 * - เลือก scope ต่อรูป: consent (อ่านกติกา) / cancel (ตอนยกเลิก) / both
 *
 * (clone โครงจาก FortuneBannerController)
 */
class FortuneConsentController extends Controller
{
    /**
     * แสดงหน้าจัดการกติกา + คลังรูป + การตั้งค่า
     */
    public function index()
    {
        $images = FortuneConsentImage::ordered()->get();
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.consent.index', [
            'images' => $images,
            'settings' => $settings,
            'defaultConsentText' => FortuneTellingSetting::defaultConsentText(),
            'defaultCancelText' => FortuneTellingSetting::defaultConsentCancelText(),
            'defaultExpireText' => FortuneTellingSetting::defaultConsentExpireText(),
            'pageTitle' => 'กติกาก่อนจองคิว',
        ]);
    }

    /**
     * ฟอร์มอัปโหลดรูปใหม่
     */
    public function create()
    {
        return view('admin.fortune.consent.create', [
            'pageTitle' => 'อัปโหลดรูปกติกาใหม่',
        ]);
    }

    /**
     * บันทึกรูปใหม่ (resize + JPEG compress)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'usage_scope' => 'required|in:consent,cancel,both,expire,all',
            'image' => 'required|image|mimes:png,jpg,jpeg|max:10240', // 10 MB
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $file = $request->file('image');
        $filename = 'consent-'.Str::random(8).'-'.time().'.jpg';
        $storagePath = 'fortune/consent/'.$filename;

        $tmpPath = tempnam(sys_get_temp_dir(), 'fconsent_').'.jpg';
        $info = $this->resizeAndSaveJpeg($file->getRealPath(), $tmpPath, 1280, 85);

        if (! $info) {
            @unlink($tmpPath);

            return redirect()->back()
                ->withInput()
                ->with('error', 'ไม่สามารถประมวลผลรูปได้ กรุณาลองรูปอื่น');
        }

        Storage::disk('public')->put($storagePath, file_get_contents($tmpPath));
        @unlink($tmpPath);

        $image = FortuneConsentImage::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'usage_scope' => $validated['usage_scope'],
            'image_path' => $storagePath,
            'width' => $info['width'],
            'height' => $info['height'],
            'file_size' => $info['size'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $validated['sort_order'] ?? ((int) FortuneConsentImage::max('sort_order') + 1),
            'uploaded_by' => auth()->id(),
        ]);

        Log::info('Admin: upload fortune consent image', [
            'image_id' => $image->id,
            'admin' => auth()->user()?->name,
            'scope' => $image->usage_scope,
        ]);

        return redirect()->route('admin.fortune.consent.index')
            ->with('success', '✅ อัปโหลดรูปกติกาสำเร็จ');
    }

    /**
     * ฟอร์มแก้ไขรูป
     */
    public function edit(FortuneConsentImage $consent)
    {
        return view('admin.fortune.consent.edit', [
            'image' => $consent,
            'pageTitle' => 'แก้ไขรูป: '.$consent->name,
        ]);
    }

    /**
     * บันทึกการแก้ไข (รองรับเปลี่ยนรูป)
     */
    public function update(Request $request, FortuneConsentImage $consent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'usage_scope' => 'required|in:consent,cancel,both,expire,all',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:10240',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'usage_scope' => $validated['usage_scope'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? $consent->sort_order,
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'consent-'.Str::random(8).'-'.time().'.jpg';
            $storagePath = 'fortune/consent/'.$filename;

            $tmpPath = tempnam(sys_get_temp_dir(), 'fconsent_').'.jpg';
            $info = $this->resizeAndSaveJpeg($file->getRealPath(), $tmpPath, 1280, 85);

            if ($info) {
                Storage::disk('public')->put($storagePath, file_get_contents($tmpPath));
                @unlink($tmpPath);

                $this->deleteOldFile($consent->image_path);

                $updateData['image_path'] = $storagePath;
                $updateData['width'] = $info['width'];
                $updateData['height'] = $info['height'];
                $updateData['file_size'] = $info['size'];
            } else {
                @unlink($tmpPath);
            }
        }

        $consent->update($updateData);

        return redirect()->route('admin.fortune.consent.index')
            ->with('success', '✅ บันทึกการแก้ไขสำเร็จ');
    }

    /**
     * ลบรูป (soft delete)
     */
    public function destroy(FortuneConsentImage $consent)
    {
        $consent->delete();

        return redirect()->route('admin.fortune.consent.index')
            ->with('success', '✅ ลบรูปสำเร็จ');
    }

    /**
     * เปิด/ปิดรูปเป็นรายตัว (AJAX)
     */
    public function toggle(FortuneConsentImage $consent)
    {
        $consent->update(['is_active' => ! $consent->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $consent->is_active,
        ]);
    }

    /**
     * บันทึกลำดับใหม่ (drag & drop)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:fortune_consent_images,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            FortuneConsentImage::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * บันทึกการตั้งค่า (ข้อความ + toggle + กลยุทธ์)
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'fortune_consent_enabled' => 'nullable|boolean',
            'fortune_consent_cancel_enabled' => 'nullable|boolean',
            'fortune_consent_expire_enabled' => 'nullable|boolean',
            'fortune_consent_pick_strategy' => 'required|in:random,rotation,sequential',
            'fortune_consent_text' => 'nullable|string|max:5000',
            'fortune_consent_cancel_text' => 'nullable|string|max:5000',
            'fortune_consent_expire_text' => 'nullable|string|max:5000',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'fortune_consent_enabled' => (bool) ($validated['fortune_consent_enabled'] ?? false),
            'fortune_consent_cancel_enabled' => (bool) ($validated['fortune_consent_cancel_enabled'] ?? false),
            'fortune_consent_expire_enabled' => (bool) ($validated['fortune_consent_expire_enabled'] ?? false),
            'fortune_consent_pick_strategy' => $validated['fortune_consent_pick_strategy'],
            // ว่าง = ใช้ default ใน model (เก็บ null)
            'fortune_consent_text' => trim((string) ($validated['fortune_consent_text'] ?? '')) ?: null,
            'fortune_consent_cancel_text' => trim((string) ($validated['fortune_consent_cancel_text'] ?? '')) ?: null,
            'fortune_consent_expire_text' => trim((string) ($validated['fortune_consent_expire_text'] ?? '')) ?: null,
        ]);
        FortuneTellingSetting::clearSettingsCache();

        return redirect()->route('admin.fortune.consent.index')
            ->with('success', '✅ บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * ลบไฟล์รูปเก่า — รองรับ legacy (images/...) และ storage (fortune/consent/...)
     */
    protected function deleteOldFile(?string $oldPath): void
    {
        if (empty($oldPath)) {
            return;
        }

        if (str_starts_with($oldPath, 'images/')) {
            $legacyFull = public_path($oldPath);
            $legacyDir = public_path('images/fortune/consent');
            if (file_exists($legacyFull) && str_starts_with((string) realpath($legacyFull), (string) realpath($legacyDir))) {
                @unlink($legacyFull);
            }

            return;
        }

        Storage::disk('public')->delete($oldPath);
    }

    /**
     * Resize + แปลงเป็น JPEG quality 85, max width = $maxWidth, รักษาอัตราส่วน
     *
     * @return array{width:int,height:int,size:int}|null
     */
    protected function resizeAndSaveJpeg(string $sourcePath, string $destPath, int $maxWidth, int $quality): ?array
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }

        [$srcW, $srcH, $type] = $info;

        $srcImg = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            default => null,
        };

        if (! $srcImg) {
            return null;
        }

        if ($srcW <= $maxWidth) {
            $newW = $srcW;
            $newH = $srcH;
        } else {
            $newW = $maxWidth;
            $newH = (int) round($maxWidth * $srcH / $srcW);
        }

        $newImg = imagecreatetruecolor($newW, $newH);

        // PNG transparency → fill ขาว (JPEG ไม่มี alpha)
        $white = imagecolorallocate($newImg, 255, 255, 255);
        imagefilledrectangle($newImg, 0, 0, $newW, $newH, $white);

        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

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
