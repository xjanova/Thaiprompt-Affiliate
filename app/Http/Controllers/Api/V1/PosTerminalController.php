<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosApiKey;
use App\Models\PosTerminal;
use App\Models\VendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * POS Terminal Controller
 *
 * จัดการ API endpoints สำหรับ POS Desktop App (MAUI)
 *
 * ระบบ 2 กุญแจ:
 * 1. Product Key: สร้างที่ POS device อัตโนมัติ (ใช้ hardware info)
 * 2. API Key: สร้างที่ Server เมื่อ POS ลงทะเบียน
 *    - API Key ไม่ส่งกลับ POS อัตโนมัติ
 *    - Admin ต้องให้ API Key กับลูกค้าเอง
 *    - ลูกค้ากรอก API Key ที่ POS เพื่อยืนยัน
 *
 * Flow:
 * 1. POS ลงทะเบียนด้วย product_key + shop_code
 * 2. Server สร้าง API Key เก็บไว้ (Admin เห็นใน Admin Panel)
 * 3. Admin ให้ API Key กับลูกค้า (ทางโทรศัพท์, email, etc.)
 * 4. ลูกค้ากรอก API Key ที่ POS
 * 5. POS ยืนยัน API Key กับ Server
 * 6. Sync สำเร็จ
 *
 * @version 1.0.0
 */
class PosTerminalController extends Controller
{
    /**
     * ดึง Secret Key จาก config (ไม่ hardcode ใน code)
     */
    private function getSecretKey(): string
    {
        return config('pos.secret_key', env('POS_SECRET_KEY', ''));
    }

    /**
     * Ping endpoint สำหรับทดสอบการเชื่อมต่อ
     *
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'pong',
            'server_time' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * ลงทะเบียน Device และสร้าง API Key อัตโนมัติ (Admin เห็น แต่ไม่ส่งกลับ)
     *
     * Flow:
     * 1. POS ส่ง encrypted Product Key + Shop Code
     * 2. Server decode Product Key ได้ข้อมูลเครื่อง (device_id, timestamp, etc.)
     * 3. สร้าง API Key อัตโนมัติ (ไม่ส่งกลับ)
     * 4. Admin เห็น API Key ใน Dashboard และส่งให้ลูกค้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerDevice(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_key' => 'required|string', // Encrypted Product Key
                'shop_code' => 'required|string|max:50',
                'device_name' => 'nullable|string|max:255',
                'device_model' => 'nullable|string|max:100',
                'platform' => 'nullable|string|max:50',
                'app_version' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Decode Product Key เพื่อดึง device_id จริง
            $decodedData = $this->decodeProductKey($validated['product_key']);

            if (!$decodedData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product Key ไม่ถูกต้อง',
                    'error_code' => 'INVALID_PRODUCT_KEY',
                ], 400);
            }

            // ค้นหาร้านค้า
            $shop = VendorStore::where('shop_code', $validated['shop_code'])
                ->orWhere('id', $validated['shop_code'])
                ->first();

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบร้านค้าจากรหัสที่ระบุ',
                    'error_code' => 'SHOP_NOT_FOUND',
                ], 404);
            }

            DB::beginTransaction();

            // ตรวจสอบว่า device_id นี้เคยลงทะเบียนแล้วหรือไม่
            $existingTerminal = PosTerminal::where('device_id', $decodedData['device_id'])->first();

            if ($existingTerminal) {
                DB::rollBack();

                // ถ้ายังไม่ยืนยัน → รอ API Key
                if (!$existingTerminal->is_verified) {
                    return response()->json([
                        'success' => true,
                        'message' => 'เครื่องนี้ลงทะเบียนแล้ว รอรับ API Key จากร้านค้า',
                        'data' => [
                            'terminal_id' => $existingTerminal->id,
                            'status' => 'pending_api_key',
                            'is_verified' => false,
                            'shop_name' => $existingTerminal->shop->name ?? '',
                            'instruction' => 'กรุณาขอ API Key จากร้านค้าแล้วนำมากรอก',
                        ],
                    ]);
                }

                // ถ้ายืนยันแล้ว → พร้อมใช้งาน
                return response()->json([
                    'success' => true,
                    'message' => 'เครื่องนี้ลงทะเบียนและยืนยันแล้ว',
                    'data' => [
                        'terminal_id' => $existingTerminal->id,
                        'status' => $existingTerminal->status,
                        'is_verified' => true,
                        'shop_name' => $existingTerminal->shop->name ?? '',
                    ],
                ]);
            }

            // สร้าง API Key ใหม่ (อัตโนมัติ, ไม่ส่งกลับ)
            $apiKey = PosApiKey::create([
                'shop_id' => $shop->id,
                'name' => "POS: {$validated['device_name']}",
                'description' => "Device ID: {$decodedData['device_id']}\nModel: {$validated['device_model']}\nสร้างอัตโนมัติเมื่อ: " . now()->format('d/m/Y H:i'),
                'is_active' => true,
                'is_blocked' => false,
            ]);

            // สร้าง Terminal ใหม่ (เก็บ device_id จริง)
            $terminal = PosTerminal::create([
                'shop_id' => $shop->id,
                'api_key_id' => $apiKey->id,
                'product_key' => $validated['product_key'], // เก็บ encrypted key ไว้
                'device_id' => $decodedData['device_id'], // เก็บ decoded device_id
                'device_name' => $validated['device_name'] ?? null,
                'device_model' => $validated['device_model'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'status' => PosTerminal::STATUS_PENDING,
                'is_verified' => false,
                'last_ip_address' => $request->ip(),
                'device_info' => [
                    'decoded_data' => $decodedData,
                    'registered_at' => now()->toISOString(),
                    'registered_ip' => $request->ip(),
                ],
            ]);

            DB::commit();

            Log::info('POS Device registered', [
                'terminal_id' => $terminal->id,
                'shop_id' => $shop->id,
                'device_id' => $decodedData['device_id'],
                'api_key_id' => $apiKey->id,
            ]);

            // ⚠️ ไม่ส่ง API Key กลับ!
            return response()->json([
                'success' => true,
                'message' => 'ลงทะเบียนสำเร็จ รอรับ API Key จากร้านค้า',
                'data' => [
                    'terminal_id' => $terminal->id,
                    'status' => 'pending_api_key',
                    'is_verified' => false,
                    'shop_name' => $shop->name,
                    'shop_id' => $shop->id,
                    'instruction' => 'กรุณาขอ API Key จากร้านค้าแล้วนำมากรอก',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Device registration failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลงทะเบียน',
                'error_code' => 'REGISTRATION_ERROR',
            ], 500);
        }
    }

    /**
     * Single-step activation: ลงทะเบียนและยืนยัน POS ในครั้งเดียว
     *
     * รวมขั้นตอน register + verify เป็นขั้นตอนเดียว
     * ต้องระบุ API Key ตอนลงทะเบียนเลย
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activate(Request $request): JsonResponse
    {
        try {
            // ตรวจสอบข้อมูล
            $validator = Validator::make($request->all(), [
                'product_key' => 'required|string|max:50',
                'shop_code' => 'required|string|max:50',
                'api_key' => 'required|string|max:100',
                'device_id' => 'required|string|max:100',
                'device_name' => 'nullable|string|max:255',
                'device_model' => 'nullable|string|max:100',
                'platform' => 'nullable|string|max:50',
                'app_version' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ค้นหาร้านค้าจาก shop_code
            $shop = VendorStore::where('shop_code', $validated['shop_code'])
                ->orWhere('id', $validated['shop_code'])
                ->first();

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบร้านค้าจากรหัสที่ระบุ',
                    'error_code' => 'SHOP_NOT_FOUND',
                ], 404);
            }

            // ค้นหา API Key
            $apiKey = PosApiKey::where('key', $validated['api_key'])->first();

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่ถูกต้อง',
                    'error_code' => 'INVALID_API_KEY',
                ], 401);
            }

            // ตรวจสอบว่า API Key ถูกบล็อกหรือไม่
            if ($apiKey->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => "API Key ถูกบล็อก: {$apiKey->blocked_reason}",
                    'error_code' => 'API_KEY_BLOCKED',
                    'blocked' => true,
                    'blocked_reason' => $apiKey->blocked_reason,
                    'blocked_at' => $apiKey->blocked_at?->toISOString(),
                ], 403);
            }

            // ตรวจสอบว่า API Key ใช้งานได้
            if (!$apiKey->isUsable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่สามารถใช้งานได้ (' . $apiKey->getStatusText() . ')',
                    'error_code' => 'API_KEY_UNUSABLE',
                ], 403);
            }

            // ตรวจสอบว่า shop_id ตรงกัน (ถ้า API Key มี shop_id)
            if ($apiKey->shop_id && $apiKey->shop_id !== $shop->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่ตรงกับร้านค้าที่ระบุ',
                    'error_code' => 'SHOP_MISMATCH',
                ], 403);
            }

            DB::beginTransaction();

            // ตรวจสอบว่า Product Key นี้เคยลงทะเบียนแล้วหรือไม่
            $terminal = PosTerminal::where('product_key', $validated['product_key'])->first();

            if ($terminal) {
                // ถ้ามีอยู่แล้ว - อัพเดทและยืนยัน
                $terminal->update([
                    'shop_id' => $shop->id,
                    'api_key_id' => $apiKey->id,
                    'device_id' => $validated['device_id'],
                    'device_name' => $validated['device_name'] ?? $terminal->device_name,
                    'device_model' => $validated['device_model'] ?? $terminal->device_model,
                    'platform' => $validated['platform'] ?? $terminal->platform,
                    'app_version' => $validated['app_version'] ?? $terminal->app_version,
                    'status' => PosTerminal::STATUS_ACTIVE,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'last_ip_address' => $request->ip(),
                ]);
            } else {
                // สร้าง Terminal ใหม่ และ activate เลย
                $terminal = PosTerminal::create([
                    'shop_id' => $shop->id,
                    'api_key_id' => $apiKey->id,
                    'product_key' => $validated['product_key'],
                    'device_id' => $validated['device_id'],
                    'device_name' => $validated['device_name'] ?? null,
                    'device_model' => $validated['device_model'] ?? null,
                    'platform' => $validated['platform'] ?? null,
                    'app_version' => $validated['app_version'] ?? null,
                    'status' => PosTerminal::STATUS_ACTIVE,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'last_ip_address' => $request->ip(),
                    'device_info' => [
                        'activated_at' => now()->toISOString(),
                        'activated_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);
            }

            // อัพเดท API Key
            $apiKey->update([
                'shop_id' => $shop->id,
                'last_used_at' => now(),
            ]);

            DB::commit();

            // Log การ activate
            Log::info('POS Terminal activated', [
                'terminal_id' => $terminal->id,
                'shop_id' => $shop->id,
                'product_key' => $validated['product_key'],
                'api_key_id' => $apiKey->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activate POS สำเร็จ พร้อมใช้งาน',
                'data' => [
                    'terminal_id' => $terminal->id,
                    'status' => 'active',
                    'is_verified' => true,
                    'shop_name' => $shop->name,
                    'shop_id' => $shop->id,
                    'verified_at' => $terminal->verified_at?->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Terminal activation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการ activate',
                'error_code' => 'ACTIVATION_ERROR',
            ], 500);
        }
    }

    /**
     * (Legacy) ลงทะเบียน POS Terminal ใหม่
     *
     * ⚠️ สำคัญ: ไม่ส่ง API Key กลับ!
     * Admin ต้องให้ API Key กับลูกค้าเอง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // ตรวจสอบข้อมูล
            $validator = Validator::make($request->all(), [
                'product_key' => 'required|string|max:50',
                'shop_code' => 'required|string|max:50',
                'device_id' => 'required|string|max:100',
                'device_name' => 'nullable|string|max:255',
                'device_model' => 'nullable|string|max:100',
                'platform' => 'nullable|string|max:50',
                'app_version' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ค้นหาร้านค้าจาก shop_code
            $shop = VendorStore::where('shop_code', $validated['shop_code'])
                ->orWhere('id', $validated['shop_code'])
                ->first();

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบร้านค้าจากรหัสที่ระบุ',
                    'error_code' => 'SHOP_NOT_FOUND',
                ], 404);
            }

            DB::beginTransaction();

            // ตรวจสอบว่า Product Key นี้เคยลงทะเบียนแล้วหรือไม่
            $existingTerminal = PosTerminal::where('product_key', $validated['product_key'])->first();

            if ($existingTerminal) {
                // ถ้ามีอยู่แล้วและยืนยันแล้ว → แจ้งให้กรอก API Key
                if ($existingTerminal->is_verified) {
                    DB::rollBack();
                    return response()->json([
                        'success' => true,
                        'message' => 'POS นี้ลงทะเบียนและยืนยันแล้ว กรุณา sync ได้เลย',
                        'data' => [
                            'terminal_id' => $existingTerminal->id,
                            'status' => $existingTerminal->status,
                            'is_verified' => true,
                            'shop_name' => $shop->name,
                            'shop_id' => $shop->id,
                        ],
                    ]);
                }

                // ถ้ามีอยู่แล้วแต่ยังไม่ยืนยัน → บอกให้รอ API Key
                DB::rollBack();
                return response()->json([
                    'success' => true,
                    'message' => 'POS นี้ลงทะเบียนแล้ว รอรับ API Key จาก Admin',
                    'data' => [
                        'terminal_id' => $existingTerminal->id,
                        'status' => 'pending_api_key',
                        'is_verified' => false,
                        'shop_name' => $shop->name,
                        'shop_id' => $shop->id,
                        'instruction' => 'กรุณาติดต่อ Admin เพื่อรับ API Key แล้วนำมากรอกที่ POS',
                    ],
                ]);
            }

            // สร้าง API Key ใหม่สำหรับร้านค้านี้
            // ⚠️ ไม่ส่ง API Key กลับ! Admin ต้องให้ลูกค้าเอง
            $apiKey = PosApiKey::create([
                'shop_id' => $shop->id,
                'key' => PosApiKey::generateKey(),
                'name' => "API Key สำหรับ {$validated['device_name']}",
                'description' => "สร้างอัตโนมัติเมื่อ POS ลงทะเบียน\nDevice: {$validated['device_name']}\nModel: {$validated['device_model']}",
                'is_active' => true,
                'is_blocked' => false,
            ]);

            // สร้าง Terminal ใหม่
            $terminal = PosTerminal::create([
                'shop_id' => $shop->id,
                'api_key_id' => $apiKey->id,
                'product_key' => $validated['product_key'],
                'device_id' => $validated['device_id'],
                'device_name' => $validated['device_name'] ?? null,
                'device_model' => $validated['device_model'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'status' => PosTerminal::STATUS_PENDING,
                'is_verified' => false,
                'last_ip_address' => $request->ip(),
                'device_info' => [
                    'registered_at' => now()->toISOString(),
                    'registered_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            DB::commit();

            // Log การลงทะเบียน
            Log::info('POS Terminal registered', [
                'terminal_id' => $terminal->id,
                'shop_id' => $shop->id,
                'product_key' => $validated['product_key'],
                'device_id' => $validated['device_id'],
                'ip' => $request->ip(),
            ]);

            // ⚠️ สำคัญ: ไม่ส่ง API Key กลับ!
            return response()->json([
                'success' => true,
                'message' => 'ลงทะเบียน POS สำเร็จ รอรับ API Key จาก Admin',
                'data' => [
                    'terminal_id' => $terminal->id,
                    'status' => 'pending_api_key',
                    'is_verified' => false,
                    'shop_name' => $shop->name,
                    'shop_id' => $shop->id,
                    'instruction' => 'กรุณาติดต่อ Admin เพื่อรับ API Key แล้วนำมากรอกที่ POS',
                    // ❌ ไม่มี api_key ใน response!
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Terminal registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลงทะเบียน',
                'error_code' => 'REGISTRATION_ERROR',
            ], 500);
        }
    }

    /**
     * ยืนยัน POS Terminal ด้วย API Key
     *
     * ขั้นตอนที่ 2: ลูกค้ากรอก API Key ที่ POS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            // ตรวจสอบข้อมูล
            $validator = Validator::make($request->all(), [
                'product_key' => 'required|string|max:50',
                'api_key' => 'required|string|max:100',
                'device_id' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ค้นหา Terminal จาก product_key
            $terminal = PosTerminal::where('product_key', $validated['product_key'])
                ->where('device_id', $validated['device_id'])
                ->first();

            if (!$terminal) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ POS Terminal นี้ กรุณาลงทะเบียนก่อน',
                    'error_code' => 'TERMINAL_NOT_FOUND',
                ], 404);
            }

            // ค้นหา API Key
            $apiKey = PosApiKey::where('key', $validated['api_key'])->first();

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่ถูกต้อง',
                    'error_code' => 'INVALID_API_KEY',
                ], 401);
            }

            // ตรวจสอบว่า API Key ถูกบล็อกหรือไม่
            if ($apiKey->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => "API Key ถูกบล็อก: {$apiKey->blocked_reason}",
                    'error_code' => 'API_KEY_BLOCKED',
                    'blocked_reason' => $apiKey->blocked_reason,
                    'blocked_at' => $apiKey->blocked_at?->toISOString(),
                ], 403);
            }

            // ตรวจสอบว่า API Key ใช้งานได้
            if (!$apiKey->isUsable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่สามารถใช้งานได้ (' . $apiKey->getStatusText() . ')',
                    'error_code' => 'API_KEY_UNUSABLE',
                ], 403);
            }

            // ตรวจสอบว่า shop_id ตรงกัน
            if ($terminal->shop_id !== $apiKey->shop_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key ไม่ตรงกับร้านค้าที่ลงทะเบียน',
                    'error_code' => 'SHOP_MISMATCH',
                ], 403);
            }

            // ยืนยัน Terminal
            $terminal->update([
                'api_key_id' => $apiKey->id,
                'status' => PosTerminal::STATUS_ACTIVE,
                'is_verified' => true,
                'verified_at' => now(),
                'last_ip_address' => $request->ip(),
            ]);

            // อัพเดท API Key
            $apiKey->update(['last_used_at' => now()]);

            // Log การยืนยัน
            Log::info('POS Terminal verified', [
                'terminal_id' => $terminal->id,
                'api_key_id' => $apiKey->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ยืนยัน POS สำเร็จ พร้อมใช้งาน',
                'data' => [
                    'terminal_id' => $terminal->id,
                    'status' => 'active',
                    'is_verified' => true,
                    'shop_name' => $terminal->shop->name ?? '',
                    'shop_id' => $terminal->shop_id,
                    'verified_at' => $terminal->verified_at?->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('POS Terminal verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการยืนยัน',
                'error_code' => 'VERIFICATION_ERROR',
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ POS Terminal และ API Key
     *
     * ใช้สำหรับ:
     * - ตรวจสอบว่า sync ได้หรือไม่
     * - ตรวจสอบว่า API Key ถูกบล็อกหรือไม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            // ดึงข้อมูลจาก headers
            $apiKey = $request->header('X-API-Key');
            $productKey = $request->header('X-Product-Key');
            $deviceId = $request->header('X-Device-ID');

            if (!$apiKey || !$productKey || !$deviceId) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน ต้องระบุ X-API-Key, X-Product-Key, X-Device-ID',
                    'error_code' => 'MISSING_HEADERS',
                ], 400);
            }

            // ค้นหา Terminal
            $terminal = PosTerminal::where('product_key', $productKey)
                ->where('device_id', $deviceId)
                ->with(['apiKey', 'shop'])
                ->first();

            if (!$terminal) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'ไม่พบ POS Terminal',
                    'error_code' => 'TERMINAL_NOT_FOUND',
                ], 404);
            }

            // ตรวจสอบว่า Terminal ถูกบล็อกหรือไม่
            if ($terminal->status === PosTerminal::STATUS_BLOCKED) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'POS Terminal ถูกบล็อก กรุณาติดต่อ Admin',
                    'error_code' => 'TERMINAL_BLOCKED',
                    'blocked' => true,
                ], 403);
            }

            if ($terminal->status === PosTerminal::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'POS Terminal ถูกระงับชั่วคราว กรุณาติดต่อ Admin',
                    'error_code' => 'TERMINAL_SUSPENDED',
                    'blocked' => true,
                ], 403);
            }

            // ตรวจสอบว่ายืนยันแล้วหรือยัง
            if (!$terminal->is_verified) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'POS ยังไม่ได้ยืนยัน กรุณากรอก API Key',
                    'error_code' => 'NOT_VERIFIED',
                    'needs_api_key' => true,
                ], 401);
            }

            // ตรวจสอบ API Key
            $posApiKey = PosApiKey::where('key', $apiKey)->first();

            if (!$posApiKey) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'API Key ไม่ถูกต้อง',
                    'error_code' => 'INVALID_API_KEY',
                ], 401);
            }

            // ตรวจสอบว่า API Key ถูกบล็อกหรือไม่
            if ($posApiKey->is_blocked) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => "API Key ถูกบล็อก\nเหตุผล: {$posApiKey->blocked_reason}\nกรุณาติดต่อ Admin",
                    'error_code' => 'API_KEY_BLOCKED',
                    'blocked' => true,
                    'blocked_reason' => $posApiKey->blocked_reason,
                    'blocked_at' => $posApiKey->blocked_at?->toISOString(),
                    'contact_admin' => true,
                ], 403);
            }

            // ตรวจสอบว่า API Key ใช้งานได้
            if (!$posApiKey->isUsable()) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'API Key ไม่สามารถใช้งานได้ (' . $posApiKey->getStatusText() . ')',
                    'error_code' => 'API_KEY_UNUSABLE',
                ], 403);
            }

            // ตรวจสอบว่าตรงกับ Terminal หรือไม่
            if ($terminal->api_key_id !== $posApiKey->id) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'API Key ไม่ตรงกับ POS Terminal นี้',
                    'error_code' => 'API_KEY_MISMATCH',
                ], 403);
            }

            // อัพเดทข้อมูล sync
            $terminal->recordSync($request->ip());

            return response()->json([
                'success' => true,
                'valid' => true,
                'message' => 'ตรวจสอบสำเร็จ พร้อม sync',
                'data' => [
                    'terminal_id' => $terminal->id,
                    'status' => $terminal->status,
                    'is_verified' => true,
                    'shop_name' => $terminal->shop->name ?? '',
                    'shop_id' => $terminal->shop_id,
                    'last_sync_at' => $terminal->last_sync_at?->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('POS Terminal validation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ',
                'error_code' => 'VALIDATION_ERROR',
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ API Key (สำหรับ POS ตรวจสอบเป็นระยะ)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkStatus(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->header('X-API-Key');
            $productKey = $request->header('X-Product-Key');

            if (!$apiKey || !$productKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน',
                ], 400);
            }

            $posApiKey = PosApiKey::where('key', $apiKey)->first();
            $terminal = PosTerminal::where('product_key', $productKey)->first();

            // เตรียม response
            $response = [
                'success' => true,
                'api_key_status' => [
                    'is_valid' => false,
                    'is_blocked' => false,
                    'blocked_reason' => null,
                    'blocked_at' => null,
                ],
                'terminal_status' => [
                    'status' => null,
                    'is_verified' => false,
                ],
            ];

            if ($posApiKey) {
                $response['api_key_status'] = [
                    'is_valid' => $posApiKey->isUsable(),
                    'is_blocked' => $posApiKey->is_blocked,
                    'blocked_reason' => $posApiKey->blocked_reason,
                    'blocked_at' => $posApiKey->blocked_at?->toISOString(),
                    'status_text' => $posApiKey->getStatusText(),
                ];
            }

            if ($terminal) {
                $response['terminal_status'] = [
                    'status' => $terminal->status,
                    'is_verified' => $terminal->is_verified,
                    'status_text' => $terminal->getStatusText(),
                ];
            }

            // ตรวจสอบว่ามีปัญหาหรือไม่
            $hasIssue = false;
            $issueMessage = null;

            if ($posApiKey && $posApiKey->is_blocked) {
                $hasIssue = true;
                $issueMessage = "API Key ถูกบล็อก: {$posApiKey->blocked_reason}\nกรุณาติดต่อ Admin";
            } elseif ($terminal && $terminal->status === PosTerminal::STATUS_BLOCKED) {
                $hasIssue = true;
                $issueMessage = 'POS Terminal ถูกบล็อก กรุณาติดต่อ Admin';
            } elseif ($terminal && $terminal->status === PosTerminal::STATUS_SUSPENDED) {
                $hasIssue = true;
                $issueMessage = 'POS Terminal ถูกระงับชั่วคราว กรุณาติดต่อ Admin';
            }

            $response['has_issue'] = $hasIssue;
            $response['issue_message'] = $issueMessage;
            $response['contact_admin'] = $hasIssue;

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    // =========================================
    // Sync Methods
    // =========================================

    /**
     * Sync สินค้าจาก Server
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncProducts(Request $request): JsonResponse
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        // ดึงสินค้าจากร้าน
        $products = \App\Models\Product::where('shop_id', $terminal->shop_id)
            ->where('is_active', true)
            ->with(['category'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'price' => $p->price,
                'cost' => $p->cost,
                'stock' => $p->stock,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name,
                'image_url' => $p->image_url,
                'updated_at' => $p->updated_at?->toISOString(),
            ]);

        // อัพเดท last sync
        $terminal->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $products,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * Sync หมวดหมู่จาก Server
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncCategories(Request $request): JsonResponse
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        // ดึงหมวดหมู่จากร้าน
        $categories = \App\Models\Category::where('shop_id', $terminal->shop_id)
            ->where('is_active', true)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color,
                'sort_order' => $c->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * อัพโหลดคำสั่งซื้อจาก POS ไป Server
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadOrders(Request $request): JsonResponse
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.local_id' => 'required|string',
            'orders.*.total' => 'required|numeric',
            'orders.*.items' => 'required|array',
            'orders.*.created_at' => 'required|date',
        ]);

        $uploadedCount = 0;
        $errors = [];

        foreach ($validated['orders'] as $orderData) {
            try {
                // ตรวจสอบว่าเคย sync แล้วหรือยัง
                $existing = \App\Models\Order::where('pos_local_id', $orderData['local_id'])
                    ->where('pos_terminal_id', $terminal->id)
                    ->first();

                if ($existing) {
                    continue; // ข้าม order ที่เคย sync แล้ว
                }

                // สร้าง order ใหม่
                $order = \App\Models\Order::create([
                    'shop_id' => $terminal->shop_id,
                    'pos_terminal_id' => $terminal->id,
                    'pos_local_id' => $orderData['local_id'],
                    'total' => $orderData['total'],
                    'status' => 'completed',
                    'created_at' => $orderData['created_at'],
                ]);

                // สร้าง order items
                foreach ($orderData['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'line_total' => $item['quantity'] * $item['price'],
                    ]);
                }

                $uploadedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'local_id' => $orderData['local_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'uploaded' => $uploadedCount,
            'errors' => $errors,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * รายงานยอดขาย
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reportSales(Request $request): JsonResponse
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'total_sales' => 'required|numeric',
            'total_orders' => 'required|integer',
            'total_items' => 'required|integer',
            'cash_sales' => 'nullable|numeric',
            'card_sales' => 'nullable|numeric',
            'other_sales' => 'nullable|numeric',
        ]);

        // บันทึกรายงาน
        \App\Models\PosDailyReport::updateOrCreate(
            [
                'terminal_id' => $terminal->id,
                'report_date' => $validated['date'],
            ],
            [
                'total_sales' => $validated['total_sales'],
                'total_transactions' => $validated['total_orders'],
                'items_sold' => $validated['total_items'],
                'total_cash' => $validated['cash_sales'] ?? 0,
                'total_card' => $validated['card_sales'] ?? 0,
                'total_qr' => $validated['other_sales'] ?? 0,
                'synced_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'บันทึกรายงานสำเร็จ'
        ]);
    }

    // =========================================
    // Private Methods
    // =========================================

    /**
     * Authenticate POS Terminal จาก headers
     *
     * @param Request $request
     * @return PosTerminal|null
     */
    private function authenticateTerminal(Request $request): ?PosTerminal
    {
        $apiKey = $request->header('X-API-Key');
        $productKey = $request->header('X-Product-Key');

        if (empty($apiKey) || empty($productKey)) {
            return null;
        }

        // หา API Key
        $posApiKey = PosApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->where('is_blocked', false) // ต้องไม่ถูกบล็อก
            ->first();

        if (!$posApiKey) {
            return null;
        }

        // ตรวจสอบวันหมดอายุ
        if ($posApiKey->expires_at && $posApiKey->expires_at->isPast()) {
            return null;
        }

        // Decode product key เพื่อหา device_id จริง
        $decodedData = $this->decodeProductKey($productKey);
        $deviceId = $decodedData ? $decodedData['device_id'] : null;

        // หา Terminal จาก device_id (ถ้า decode ได้) หรือ product_key
        $terminal = PosTerminal::where(function ($query) use ($productKey, $deviceId) {
            if ($deviceId) {
                $query->where('device_id', $deviceId);
            } else {
                $query->where('product_key', $productKey);
            }
        })
            ->where('api_key_id', $posApiKey->id)
            ->where('status', PosTerminal::STATUS_ACTIVE)
            ->where('is_verified', true)
            ->first();

        if ($terminal) {
            $terminal->recordSync($request->ip());
        }

        return $terminal;
    }

    /**
     * Decode Product Key เพื่อดึงข้อมูล device จริง
     *
     * Product Key format: TP-POS-XXXX-XXXX-XXXX-XXXX (Base64 encoded + encrypted)
     * เมื่อ decode แล้วจะได้: device_id, timestamp, checksum
     *
     * @param string $encryptedKey
     * @return array|null
     */
    private function decodeProductKey(string $encryptedKey): ?array
    {
        try {
            // ถ้าเป็น format TP-POS-XXXX-XXXX-XXXX-XXXX
            if (preg_match('/^TP-POS-(.+)$/', $encryptedKey, $matches)) {
                $encoded = $matches[1];
                // ลบ dash และ decode
                $encoded = str_replace('-', '', $encoded);
            } else {
                // ถ้าเป็น raw encoded string
                $encoded = $encryptedKey;
            }

            // ลอง Base64 decode
            $decoded = base64_decode($encoded, true);

            if ($decoded === false) {
                // ถ้า decode ไม่ได้ → ใช้ raw value เป็น device_id
                return [
                    'device_id' => hash('sha256', $encryptedKey . $this->getSecretKey()),
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'hash_fallback',
                ];
            }

            // ลอง XOR decrypt ด้วย secret key
            $decrypted = $this->xorDecrypt($decoded, $this->getSecretKey());

            // ลอง parse เป็น JSON
            $data = json_decode($decrypted, true);

            if ($data && isset($data['device_id'])) {
                return [
                    'device_id' => $data['device_id'],
                    'timestamp' => $data['timestamp'] ?? null,
                    'checksum' => $data['checksum'] ?? null,
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'json',
                ];
            }

            // ถ้าไม่ใช่ JSON → ใช้ decrypted string เป็น device_id
            if (!empty($decrypted) && strlen($decrypted) > 5) {
                return [
                    'device_id' => $decrypted,
                    'raw_key' => $encryptedKey,
                    'decoded_method' => 'raw',
                ];
            }

            // Fallback: hash the key
            return [
                'device_id' => hash('sha256', $encryptedKey . $this->getSecretKey()),
                'raw_key' => $encryptedKey,
                'decoded_method' => 'hash_fallback',
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to decode product key', [
                'key' => substr($encryptedKey, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            // Fallback: hash the key
            return [
                'device_id' => hash('sha256', $encryptedKey . $this->getSecretKey()),
                'raw_key' => $encryptedKey,
                'decoded_method' => 'error_fallback',
            ];
        }
    }

    /**
     * XOR decrypt/encrypt
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    private function xorDecrypt(string $data, string $key): string
    {
        $result = '';
        $keyLength = strlen($key);

        for ($i = 0; $i < strlen($data); $i++) {
            $result .= chr(ord($data[$i]) ^ ord($key[$i % $keyLength]));
        }

        return $result;
    }
}
