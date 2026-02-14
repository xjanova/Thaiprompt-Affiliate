<?php

namespace App\Http\Controllers;

use App\Models\SoftwareDownload;
use App\Models\SoftwareLicense;
use App\Models\SoftwareProduct;
use App\Services\LicenseKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SoftwareDownloadController
 *
 * จัดการการดาวน์โหลดซอฟต์แวร์
 */
class SoftwareDownloadController extends Controller
{
    /**
     * @var LicenseKeyService
     */
    protected $licenseKeyService;

    /**
     * Constructor
     */
    public function __construct(LicenseKeyService $licenseKeyService)
    {
        $this->licenseKeyService = $licenseKeyService;
    }

    /**
     * สร้าง Download Token สำหรับ License
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createToken(Request $request, SoftwareLicense $license)
    {
        try {
            // ตรวจสอบว่า user เป็นเจ้าของ license
            if ($license->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ดาวน์โหลดซอฟต์แวร์นี้',
                ], 403);
            }

            // สร้าง download token
            $download = $this->licenseKeyService->createDownloadToken($license);

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $download->download_token,
                    'expires_at' => $download->token_expires_at->toIso8601String(),
                    'download_url' => route('software.download.file', ['token' => $download->download_token]),
                ],
                'message' => 'สร้าง Download Token สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ดาวน์โหลดไฟล์ผ่าน Token
     *
     * @return StreamedResponse|Response
     */
    public function downloadByToken(string $token)
    {
        try {
            // ตรวจสอบ token และดึงข้อมูล
            $data = $this->licenseKeyService->validateDownloadToken($token);

            $download = $data['download'];
            $product = $data['product'];

            // ดาวน์โหลดไฟล์ตามประเภทการจัดเก็บ
            return $this->downloadFile($product, $download);
        } catch (\Exception $e) {
            return response()->view('errors.download-failed', [
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ดาวน์โหลดไฟล์จาก Product (ต้องมี License)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function download(SoftwareProduct $product)
    {
        try {
            // ตรวจสอบว่า user มี license ที่ valid
            $license = SoftwareLicense::where('software_product_id', $product->id)
                ->where('user_id', auth()->id())
                ->active()
                ->first();

            if (! $license) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มี License สำหรับซอฟต์แวร์นี้',
                ], 403);
            }

            // สร้าง download token
            $download = $this->licenseKeyService->createDownloadToken($license);

            // Redirect ไปยัง download URL
            return redirect()->route('software.download.file', ['token' => $download->download_token]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ดาวน์โหลดไฟล์ตามประเภทการจัดเก็บ
     *
     * @return StreamedResponse|Response
     */
    protected function downloadFile(SoftwareProduct $product, SoftwareDownload $download)
    {
        // เริ่มต้นการดาวน์โหลด
        $download->update([
            'status' => SoftwareDownload::STATUS_DOWNLOADING,
            'started_at' => now(),
        ]);

        try {
            $response = match ($product->storage_type) {
                'url' => $this->downloadFromUrl($product, $download),
                's3' => $this->downloadFromS3($product, $download),
                'google_drive' => $this->downloadFromGoogleDrive($product, $download),
                'local' => $this->downloadFromLocal($product, $download),
                default => throw new \Exception('ประเภทการจัดเก็บไม่ถูกต้อง'),
            };

            // บันทึกการดาวน์โหลดสำเร็จ
            $this->licenseKeyService->completeDownload($download);

            return $response;
        } catch (\Exception $e) {
            // บันทึก error
            $download->markFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * ดาวน์โหลดจาก URL
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function downloadFromUrl(SoftwareProduct $product, SoftwareDownload $download)
    {
        // Redirect ไปยัง URL ภายนอก
        return redirect($product->file_url);
    }

    /**
     * ดาวน์โหลดจาก AWS S3
     *
     * @return StreamedResponse
     */
    protected function downloadFromS3(SoftwareProduct $product, SoftwareDownload $download)
    {
        // สร้าง temporary signed URL (valid 5 นาที)
        $url = Storage::disk('s3')->temporaryUrl(
            $product->s3_key,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'attachment; filename="'.$product->slug.'.zip"',
            ]
        );

        return redirect($url);
    }

    /**
     * ดาวน์โหลดจาก Google Drive
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function downloadFromGoogleDrive(SoftwareProduct $product, SoftwareDownload $download)
    {
        // สร้าง Google Drive download URL
        $fileId = $product->google_drive_file_id;
        $url = "https://drive.google.com/uc?export=download&id={$fileId}";

        return redirect($url);
    }

    /**
     * ดาวน์โหลดจาก Local Server
     *
     * @return StreamedResponse
     */
    protected function downloadFromLocal(SoftwareProduct $product, SoftwareDownload $download)
    {
        $filePath = $product->local_file_path;

        // ตรวจสอบว่าไฟล์มีอยู่จริง
        if (! Storage::disk('local')->exists($filePath)) {
            throw new \Exception('ไม่พบไฟล์ซอฟต์แวร์');
        }

        // ดาวน์โหลดไฟล์
        return Storage::disk('local')->download(
            $filePath,
            $product->slug.'.zip',
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$product->slug.'.zip"',
            ]
        );
    }

    /**
     * แสดงหน้า Download History
     *
     * @return \Illuminate\View\View
     */
    public function history()
    {
        $downloads = SoftwareDownload::where('user_id', auth()->id())
            ->with(['product', 'license'])
            ->latest()
            ->paginate(20);

        return view('user.downloads.history', [
            'downloads' => $downloads,
            'pageTitle' => 'ประวัติการดาวน์โหลด',
        ]);
    }
}
