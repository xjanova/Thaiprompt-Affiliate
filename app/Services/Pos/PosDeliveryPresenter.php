<?php

namespace App\Services\Pos;

use App\Models\DeliveryHandover;
use App\Models\Order;
use App\Models\PosDeliveryRequest;
use App\Models\RiderJob;
use App\Models\VendorStore;

/**
 * รูปแบบ JSON ของคำขอส่งไรเดอร์จาก POS ฝั่งเครื่อง POS (ตาม contract v1)
 *
 * ❗ ห้ามส่ง token (ยกเว้น qr_payload ตอนสร้าง), เบอร์ลูกค้า, ที่อยู่เต็ม หรือข้อมูลไรเดอร์แบบไม่ปิดบัง
 */
class PosDeliveryPresenter
{
    /**
     * ผลการสร้างคำขอ (POST /api/pos/delivery-requests)
     *
     * @return array<string, mixed>
     */
    public function created(PosDeliveryRequest $request, ?VendorStore $store, bool $pushSent): array
    {
        return [
            'id' => (int) $request->id,
            'status' => (string) $request->status,
            'qr_payload' => $request->qrPayload(),
            'expires_at' => $request->expires_at?->toIso8601String(),
            'store' => $store ? ['id' => (int) $store->id, 'name' => (string) $store->store_name] : null,
            'items' => $this->items($request),
            'subtotal' => round((float) $request->subtotal, 2),
            'push_sent' => $pushSent,
        ];
    }

    /**
     * สถานะคำขอ (GET /api/pos/delivery-requests/{id}) — order/customer/rider_job/handover เป็น null จนกว่าจะมี
     *
     * @return array<string, mixed>
     */
    public function forTerminal(PosDeliveryRequest $request): array
    {
        /** @var Order|null $order */
        $order = $request->order_id ? Order::with('user')->find($request->order_id) : null;
        $job = $order ? RiderJob::forSource($order)->with('rider')->latest('id')->first() : null;
        $handover = $job ? DeliveryHandover::where('rider_job_id', $job->id)->latest('id')->first() : null;

        $updatedAt = collect([$request->updated_at, $order?->updated_at, $job?->updated_at, $handover?->updated_at])
            ->filter()
            ->max();

        return [
            'id' => (int) $request->id,
            'status' => (string) $request->status,
            'expires_at' => $request->expires_at?->toIso8601String(),
            'subtotal' => round((float) $request->subtotal, 2),
            // ค่าส่งรู้เมื่อลูกค้าเลือกที่อยู่และจ่ายแล้วเท่านั้น
            'delivery_fee' => $order ? round((float) $order->shipping_fee, 2) : null,
            'total' => $order ? round((float) $order->total_amount, 2) : null,
            'order' => $order ? [
                'id' => (int) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => (string) $order->status,
            ] : null,
            'customer' => $order ? $this->customer($order) : null,
            'rider_job' => $job ? $this->riderJob($job) : null,
            'handover' => $handover ? ['status' => (string) $handover->status] : null,
            'updated_at' => $updatedAt?->toIso8601String(),
        ];
    }

    /**
     * รายการสินค้าที่เก็บไว้ตอนสร้าง (ราคา ณ เวลาสร้างคำขอ)
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(PosDeliveryRequest $request): array
    {
        return collect(is_array($request->items) ? $request->items : [])
            ->map(function ($row) {
                $qty = (int) ($row['qty'] ?? 0);
                $price = round((float) ($row['price'] ?? 0), 2);

                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'sku' => (string) ($row['sku'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => round($price * $qty, 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * ลูกค้าแบบย่อ (ชื่อแรก + อักษรแรกของนามสกุล / ที่อยู่สั้นๆ ให้ร้านรู้ว่าออเดอร์ของใคร)
     *
     * @return array{display_name: string, address_short: string|null}
     */
    private function customer(Order $order): array
    {
        $snapshot = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : [];
        $name = trim((string) ($snapshot['recipient_name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($order->user?->name ?? ''));
        }

        $line = trim(implode(' ', array_filter([
            $snapshot['address_line_1'] ?? null,
            $snapshot['district'] ?? null,
        ])));

        return [
            'display_name' => self::shortName($name),
            'address_short' => $line !== '' ? mb_strimwidth($line, 0, 40, '…') : null,
        ];
    }

    /**
     * งานไรเดอร์ + ไรเดอร์แบบปิดบางส่วน
     *
     * @return array<string, mixed>
     */
    private function riderJob(RiderJob $job): array
    {
        $rider = $job->rider_id ? $job->rider : null;

        return [
            'id' => (int) $job->id,
            'job_number' => (string) $job->job_number,
            'status' => self::jobStatus((string) $job->status),
            'rider' => $rider ? [
                'display_name' => self::shortName((string) $rider->full_name),
                'plate_masked' => self::maskPlate($rider->vehicle_plate),
                'phone_masked' => self::maskPhone($rider->phone),
            ] : null,
        ];
    }

    /**
     * สถานะงานไรเดอร์ตาม contract (pending → searching, accepted → assigned, ที่เหลือใช้ชื่อเดิม)
     */
    public static function jobStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'searching',
            'accepted' => 'assigned',
            default => $status,
        };
    }

    /**
     * ชื่อแรก + อักษรแรกของนามสกุล เช่น "สมชาย ใจดี" → "สมชาย ใ."
     *
     * ใช้ HandoverService::shortName ของไรเดอร์รอบ 2 เมื่อมีในระบบ (สูตรเดียวกันทุกหน้าจอ)
     */
    public static function shortName(string $name): string
    {
        $handover = 'App\\Services\\Rider\\HandoverService';
        if (method_exists($handover, 'shortName')) {
            return $handover::shortName($name);
        }

        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'ผู้ใช้';
        }

        return isset($parts[1]) ? $parts[0].' '.mb_substr($parts[1], 0, 1).'.' : $parts[0];
    }

    /**
     * ทะเบียนแบบปิดบางส่วน เช่น "1กข 1234" → "1กข **34"
     *
     * ใช้ HandoverService::maskPlate ของไรเดอร์รอบ 2 เมื่อมีในระบบ
     */
    public static function maskPlate(?string $plate): ?string
    {
        $handover = 'App\\Services\\Rider\\HandoverService';
        if (method_exists($handover, 'maskPlate')) {
            return $handover::maskPlate($plate);
        }

        $plate = trim((string) $plate);
        if ($plate === '') {
            return null;
        }

        $length = mb_strlen($plate);
        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        $head = mb_substr($plate, 0, $length - 2);
        $tail = mb_substr($plate, -2);
        $masked = preg_replace_callback('/\d+$/u', fn ($m) => str_repeat('*', strlen($m[0])), $head);

        return ($masked ?? $head).$tail;
    }

    /**
     * เบอร์โทรแบบปิดบางส่วน เช่น "0812341234" → "08x-xxx-1234"
     */
    public static function maskPhone(?string $phone): ?string
    {
        $digits = (string) preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) === 11 && str_starts_with($digits, '66')) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) < 6) {
            return null;
        }

        return substr($digits, 0, 2).'x-xxx-'.substr($digits, -4);
    }
}
