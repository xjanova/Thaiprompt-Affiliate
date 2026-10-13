<?php

namespace App\Services\TpixPay;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * การตั้งค่าที่แอดมินปรับได้ของระบบชำระด้วย TPIX (ตาราง settings กลุ่ม tpix_pay)
 *
 * อ่านผ่าน Cache 60 วิ + จำในโปรเซสไม่เกิน 30 วิ (หน้ารายการสินค้าเรียกหลายสิบครั้งต่อหน้า)
 * ❗ ความจำในโปรเซสต้องมีอายุ — queue worker อยู่ยาว ถ้าจำถาวรจะถือค่าเก่าหลังแอดมินแก้
 */
class TpixPaySettings
{
    public const KEY_ENABLED = 'tpix_pay.enabled';

    public const KEY_RECEIVE_ADDRESS = 'tpix_pay.receive_address';

    public const KEY_CONFIRMATIONS = 'tpix_pay.confirmations';

    public const KEY_SHOW_PRICES = 'tpix_pay.show_prices';

    public const KEY_MAX_PER_PAYMENT = 'tpix_pay.max_per_payment_thb';

    public const KEY_MAX_PER_USER_DAY = 'tpix_pay.max_per_user_day_thb';

    private const CACHE_KEY = 'tpix_pay:settings:v1';

    private const MEMO_SECONDS = 30;

    /** @var array{values: array<string, mixed>, at: int}|null */
    private ?array $memo = null;

    /**
     * @return array{enabled: bool, receive_address: string, confirmations: int, show_prices: bool, max_per_payment_thb: float, max_per_user_day_thb: float}
     */
    public function all(): array
    {
        if ($this->memo !== null && (time() - $this->memo['at']) < self::MEMO_SECONDS) {
            return $this->memo['values'];
        }

        $values = Cache::remember(self::CACHE_KEY, 60, fn () => $this->load());
        $this->memo = ['values' => $values, 'at' => time()];

        return $values;
    }

    /** เปิดรับชำระด้วย TPIX (สวิตช์แอดมิน + มีกระเป๋ารับเงินที่ถูกรูปแบบ) */
    public function paymentsEnabled(): bool
    {
        $s = $this->all();

        return $s['enabled'] && self::isValidAddress($s['receive_address']);
    }

    /** แสดงราคา TPIX บนหน้าสินค้า (แยกจากการรับชำระ — โชว์ราคาได้แม้ยังไม่เปิดรับเงิน) */
    public function showPrices(): bool
    {
        return $this->all()['show_prices'];
    }

    public function receiveAddress(): string
    {
        return $this->all()['receive_address'];
    }

    public function confirmations(): int
    {
        return $this->all()['confirmations'];
    }

    /** เพดานต่อรายการ (บาท) — 0 = ไม่จำกัด */
    public function maxPerPaymentThb(): float
    {
        return $this->all()['max_per_payment_thb'];
    }

    /** เพดานต่อผู้ใช้ต่อ 24 ชม. (บาท) — 0 = ไม่จำกัด */
    public function maxPerUserDayThb(): float
    {
        return $this->all()['max_per_user_day_thb'];
    }

    /**
     * บันทึกค่าจากหน้าแอดมิน
     *
     * @param  array{enabled?: bool, receive_address?: string, confirmations?: int, show_prices?: bool, max_per_payment_thb?: float, max_per_user_day_thb?: float}  $values
     */
    public function save(array $values): void
    {
        if (array_key_exists('enabled', $values)) {
            Setting::set(self::KEY_ENABLED, $values['enabled'] ? '1' : '0', 'boolean', 'tpix_pay');
        }
        if (array_key_exists('show_prices', $values)) {
            Setting::set(self::KEY_SHOW_PRICES, $values['show_prices'] ? '1' : '0', 'boolean', 'tpix_pay');
        }
        if (array_key_exists('receive_address', $values)) {
            Setting::set(self::KEY_RECEIVE_ADDRESS, trim((string) $values['receive_address']), 'string', 'tpix_pay');
        }
        if (array_key_exists('confirmations', $values)) {
            Setting::set(self::KEY_CONFIRMATIONS, (string) max(1, min(64, (int) $values['confirmations'])), 'integer', 'tpix_pay');
        }
        foreach ([self::KEY_MAX_PER_PAYMENT => 'max_per_payment_thb', self::KEY_MAX_PER_USER_DAY => 'max_per_user_day_thb'] as $key => $field) {
            if (array_key_exists($field, $values)) {
                Setting::set($key, (string) round(max(0, (float) $values[$field]), 2), 'float', 'tpix_pay');
            }
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->memo = null;
    }

    /**
     * ที่อยู่กระเป๋า EVM ถูกรูปแบบ (0x + hex 40 ตัว) — ไม่บังคับ checksum ตัวพิมพ์ (กระเป๋าส่วนใหญ่ส่งตัวเล็ก)
     */
    public static function isValidAddress(?string $address): bool
    {
        return is_string($address) && preg_match('/^0x[0-9a-fA-F]{40}$/', $address) === 1
            && strtolower($address) !== '0x0000000000000000000000000000000000000000';
    }

    /**
     * @return array{enabled: bool, receive_address: string, confirmations: int, show_prices: bool, max_per_payment_thb: float, max_per_user_day_thb: float}
     */
    private function load(): array
    {
        $defaults = (array) config('tpix_pay.defaults', []);

        try {
            $rows = Setting::whereIn('key', [
                self::KEY_ENABLED,
                self::KEY_RECEIVE_ADDRESS,
                self::KEY_CONFIRMATIONS,
                self::KEY_SHOW_PRICES,
                self::KEY_MAX_PER_PAYMENT,
                self::KEY_MAX_PER_USER_DAY,
            ])->pluck('value', 'key');
        } catch (\Throwable $e) {
            Log::warning('TpixPaySettings: read failed', ['error' => $e->getMessage()]);
            $rows = collect();
        }

        $bool = static fn ($v, bool $default): bool => $v === null ? $default : in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);

        return [
            'enabled' => $bool($rows[self::KEY_ENABLED] ?? null, (bool) ($defaults['enabled'] ?? false)),
            'receive_address' => trim((string) ($rows[self::KEY_RECEIVE_ADDRESS] ?? ($defaults['receive_address'] ?? ''))),
            'confirmations' => max(1, (int) ($rows[self::KEY_CONFIRMATIONS] ?? ($defaults['confirmations'] ?? 3))),
            // ค่าเริ่มต้น: โชว์ราคา TPIX บนหน้าสินค้า (เจ้าของสั่งให้โชว์ทุกหน้า)
            'show_prices' => $bool($rows[self::KEY_SHOW_PRICES] ?? null, true),
            // เพดานกันความเสี่ยงสภาพคล่อง: พูล DEX ยังบาง เหรียญที่รับมาขายคืนเป็นเงินบาทตามราคานี้ไม่ได้ทั้งหมด
            'max_per_payment_thb' => max(0.0, (float) ($rows[self::KEY_MAX_PER_PAYMENT] ?? ($defaults['max_per_payment_thb'] ?? 20000))),
            'max_per_user_day_thb' => max(0.0, (float) ($rows[self::KEY_MAX_PER_USER_DAY] ?? ($defaults['max_per_user_day_thb'] ?? 50000))),
        ];
    }
}
