<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ที่อยู่จัดส่งของผู้ใช้ในแอป (SHOP-08)
 *
 * GET/POST /api/v1/addresses · PUT/DELETE /api/v1/addresses/{id} · POST /api/v1/addresses/{id}/default
 *
 * ใช้ตาราง shipping_addresses ตัวเดียวกับหน้าเว็บ (/shipping-addresses) — มีพิกัด latitude/longitude
 * สำหรับส่งด้วยไรเดอร์ · ผู้ใช้เห็น/แก้ได้เฉพาะที่อยู่ของตัวเอง (ไม่เจอ = 404 ไม่บอกว่ามีของคนอื่น)
 */
class AddressApiController extends Controller
{
    /** จำนวนที่อยู่สูงสุดต่อผู้ใช้ */
    private const MAX_ADDRESSES = 20;

    /**
     * GET /api/v1/addresses
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = ShippingAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ShippingAddress $a) => $this->present($a))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'ดึงที่อยู่จัดส่งสำเร็จ',
            'data' => $addresses,
        ]);
    }

    /**
     * POST /api/v1/addresses
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules(true), $this->messages());
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = $request->user();

        if (ShippingAddress::where('user_id', $user->id)->count() >= self::MAX_ADDRESSES) {
            return response()->json([
                'success' => false,
                'code' => 'ADDRESS_LIMIT',
                'message' => 'บันทึกที่อยู่ได้สูงสุด '.self::MAX_ADDRESSES.' ที่อยู่ กรุณาลบที่อยู่ที่ไม่ใช้ก่อน',
            ], 422);
        }

        try {
            $address = DB::transaction(function () use ($request, $user) {
                $data = $this->payload($request);
                $data['user_id'] = $user->id;
                $data['country'] = $data['country'] ?? 'Thailand';

                $hasDefault = ShippingAddress::where('user_id', $user->id)->where('is_default', true)->exists();
                $makeDefault = $request->boolean('is_default') || ! $hasDefault;
                $data['is_default'] = false;

                $address = ShippingAddress::create($data);

                if ($makeDefault) {
                    $address->setAsDefault();
                }

                return $address->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'บันทึกที่อยู่จัดส่งแล้ว',
                'data' => $this->present($address),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Address API store failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->serverError();
        }
    }

    /**
     * PUT /api/v1/addresses/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = $this->owned($request, $id);
        if (! $address) {
            return $this->notFound();
        }

        $validator = Validator::make($request->all(), $this->rules(false), $this->messages());
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        try {
            $address = DB::transaction(function () use ($request, $address) {
                $address->update($this->payload($request, false));

                if ($request->has('is_default') && $request->boolean('is_default')) {
                    $address->setAsDefault();
                }

                return $address->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขที่อยู่จัดส่งแล้ว',
                'data' => $this->present($address),
            ]);
        } catch (\Throwable $e) {
            Log::error('Address API update failed', ['address_id' => $id, 'error' => $e->getMessage()]);

            return $this->serverError();
        }
    }

    /**
     * DELETE /api/v1/addresses/{id} — ลบที่อยู่เริ่มต้น → ตั้งที่อยู่ล่าสุดที่เหลือเป็นค่าเริ่มต้นแทน
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = $this->owned($request, $id);
        if (! $address) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($address) {
                $wasDefault = (bool) $address->is_default;
                $userId = $address->user_id;

                // soft delete — ออเดอร์เก่ายังอ้างถึงได้ (orders.shipping_address_id + snapshot)
                $address->is_default = false;
                $address->save();
                $address->delete();

                if ($wasDefault) {
                    $next = ShippingAddress::where('user_id', $userId)->orderByDesc('updated_at')->first();
                    $next?->setAsDefault();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'ลบที่อยู่จัดส่งแล้ว',
                'data' => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('Address API destroy failed', ['address_id' => $id, 'error' => $e->getMessage()]);

            return $this->serverError();
        }
    }

    /**
     * POST /api/v1/addresses/{id}/default
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = $this->owned($request, $id);
        if (! $address) {
            return $this->notFound();
        }

        DB::transaction(fn () => $address->setAsDefault());

        return response()->json([
            'success' => true,
            'message' => 'ตั้งเป็นที่อยู่เริ่มต้นแล้ว',
            'data' => $this->present($address->fresh()),
        ]);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes|required';

        return [
            'recipient_name' => $required.'|string|max:255',
            'phone_number' => [$creating ? 'required' : 'sometimes', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'address_line_1' => $required.'|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => $required.'|string|max:255',
            'postal_code' => [$creating ? 'required' : 'sometimes', 'string', 'regex:/^[0-9]{5}$/'],
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
            'notes' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'recipient_name.required' => 'กรุณากรอกชื่อผู้รับ',
            'phone_number.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone_number.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
            'address_line_1.required' => 'กรุณากรอกที่อยู่',
            'province.required' => 'กรุณาเลือกจังหวัด',
            'postal_code.required' => 'กรุณากรอกรหัสไปรษณีย์',
            'postal_code.regex' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'latitude.between' => 'พิกัดละติจูดไม่ถูกต้อง',
            'longitude.between' => 'พิกัดลองจิจูดไม่ถูกต้อง',
            'latitude.required_with' => 'กรุณาปักหมุดตำแหน่งให้ครบ',
            'longitude.required_with' => 'กรุณาปักหมุดตำแหน่งให้ครบ',
        ];
    }

    /**
     * ค่าที่บันทึกได้จาก request (ไม่รับ user_id/is_default จาก client ตรงๆ)
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request, bool $creating = true): array
    {
        $fields = [
            'recipient_name', 'phone_number', 'address_line_1', 'address_line_2',
            'sub_district', 'district', 'province', 'postal_code', 'country', 'notes',
        ];

        $data = [];
        foreach ($fields as $field) {
            if ($creating || $request->has($field)) {
                $value = $request->input($field);
                $data[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        if ($creating || $request->has('latitude') || $request->has('longitude')) {
            $lat = $request->input('latitude');
            $lng = $request->input('longitude');
            $data['latitude'] = is_numeric($lat) ? round((float) $lat, 7) : null;
            $data['longitude'] = is_numeric($lng) ? round((float) $lng, 7) : null;
        }

        return $data;
    }

    private function owned(Request $request, int $id): ?ShippingAddress
    {
        return ShippingAddress::where('user_id', $request->user()->id)->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ShippingAddress $address): array
    {
        return [
            'id' => (int) $address->id,
            'recipient_name' => $address->recipient_name,
            'phone_number' => $address->phone_number,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'sub_district' => $address->sub_district,
            'district' => $address->district,
            'province' => $address->province,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'latitude' => $address->hasLocation() ? (float) $address->latitude : null,
            'longitude' => $address->hasLocation() ? (float) $address->longitude : null,
            'has_location' => $address->hasLocation(),
            'notes' => $address->notes,
            'is_default' => (bool) $address->is_default,
            'full_address' => $address->full_address,
            'updated_at' => $address->updated_at?->toISOString(),
        ];
    }

    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => $validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
            'errors' => $validator->errors(),
        ], 422);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ADDRESS_NOT_FOUND',
            'message' => 'ไม่พบที่อยู่จัดส่งนี้',
        ], 404);
    }

    private function serverError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ADDRESS_ERROR',
            'message' => 'บันทึกที่อยู่ไม่สำเร็จ กรุณาลองใหม่',
        ], 500);
    }
}
