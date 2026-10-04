<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Media\ProfilePhotoException;
use App\Services\Media\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * รูปโปรไฟล์ถ่ายสด (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * - GET  /api/v1/me/profile-photo   สถานะรูปของตัวเอง
 * - POST /api/v1/me/profile-photo   ส่งรูปที่ถ่ายจากกล้อง (multipart `photo`)
 * - GET  /api/v1/media/profile-photo/{subject}/{viewer}/{version}  (signed, นอกกลุ่ม auth)
 *        รูปพร้อมลายน้ำรหัสผู้ดู — ลิงก์สร้างจาก ProfilePhotoService::urlFor() เท่านั้น
 */
class ProfilePhotoApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(private readonly ProfilePhotoService $photos) {}

    /**
     * GET /me/profile-photo → { has_photo, taken_at, photo_url, required }
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok($this->photos->statusPayload($user));
    }

    /**
     * POST /me/profile-photo (multipart photo: jpeg/png/webp ≤ 8 MB) → เหมือน GET
     *
     * ไฟล์ถูกเข้ารหัสใหม่เป็น JPEG ≤ 1024 px (ตัด EXIF/GPS) แล้วเก็บบน private disk
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.ProfilePhotoService::MAX_UPLOAD_KB,
            ],
        ], [
            'photo.required' => 'กรุณาถ่ายรูปโปรไฟล์',
            'photo.file' => 'กรุณาถ่ายรูปโปรไฟล์',
            'photo.uploaded' => 'อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่',
            'photo.mimes' => 'รองรับเฉพาะรูป JPG, PNG หรือ WebP',
            'photo.mimetypes' => 'รองรับเฉพาะรูป JPG, PNG หรือ WebP',
            'photo.max' => 'รูปมีขนาดใหญ่เกิน 8 MB',
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        /** @var User $user */
        $user = $request->user();

        try {
            $user = $this->photos->store($user, $request->file('photo'));
        } catch (ProfilePhotoException $e) {
            return $this->fail($e->errorCode, $e->getMessage(), $e->status);
        } catch (\Throwable $e) {
            Log::error('ProfilePhotoApi: store failed', ['user_id' => $user->getKey(), 'error' => class_basename($e)]);

            return $this->fail('PHOTO_SAVE_FAILED', 'บันทึกรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($this->photos->statusPayload($user), 'บันทึกรูปโปรไฟล์เรียบร้อย');
    }

    /**
     * GET /media/profile-photo/{subject}/{viewer}/{version} — ผ่าน middleware `signed` แล้วเท่านั้น
     *
     * ไม่บอกเหตุผลที่หาไม่เจอ (ไม่มีผู้ใช้/ไม่มีรูป/ถ่ายใหม่แล้ว = 404 เหมือนกัน)
     */
    public function file(int $subject, string $viewer, string $version): Response
    {
        $user = User::query()->find($subject);

        $jpeg = null;
        if ($user) {
            try {
                $jpeg = $this->photos->watermarkedJpeg($user, $viewer, $version);
            } catch (\Throwable $e) {
                Log::warning('ProfilePhotoApi: render failed', ['subject_id' => $subject, 'error' => class_basename($e)]);
                $jpeg = null;
            }
        }

        $headers = [
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ];

        if ($jpeg === null) {
            return response('', 404, array_merge($headers, ['Cache-Control' => 'private, no-store, max-age=0']));
        }

        return response($jpeg, 200, array_merge($headers, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($jpeg),
            'Content-Disposition' => 'inline; filename="profile-'.$subject.'.jpg"',
        ]));
    }
}
