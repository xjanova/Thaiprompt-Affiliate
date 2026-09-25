<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\DeliveryFeeCalculator;
use App\Services\Pricing\PlatformShareAdvisor;
use App\Services\Pricing\PricingEngine;
use App\Services\SellerPayoutService;
use App\Services\SettingAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * หน้าแอดมิน "ส่วนแบ่งรายได้ & GP" (admin.pricing.settings)
 *
 * รวมค่าตั้งเรื่องเงินที่แบ่งกันระหว่าง แพลตฟอร์ม / ร้านค้า / ไรเดอร์ ไว้หน้าเดียว:
 *  - โปรฯ GP ฟรีช่วงเปิดตัว + วันสิ้นสุด, อัตรา GP มาตรฐาน/ขั้นต่ำ (อีคอมเมิร์ซ), GP ตลาดสด
 *  - ค่าแนะนำ (เฉพาะเว็บ), อัตรา VAT, ร้านทางการจด VAT
 *  - ส่วนแบ่งไรเดอร์จากค่าส่ง (ค่าส่งอื่นๆ อยู่หน้า admin.riders.settings)
 *  - วันปล่อยเงินร้านอัตโนมัติหลังส่งพัสดุ
 * ทุกการเปลี่ยนแปลงบันทึกประวัติ (ใคร/เมื่อไหร่/ค่าเดิม → ค่าใหม่) ผ่าน SettingAuditService
 * ตัวจำลอง (simulate) ใช้ PricingEngine::breakdown + DeliveryFeeCalculator ตัวจริง ตัวเลขจึงตรงกับตอนตัดเงินจริง
 */
class PricingSettingsController extends Controller
{
    /**
     * ฟิลด์ในฟอร์ม => ข้อมูล key ใน settings
     *
     * @var array<string, array{key: string, type: string, group: string, label: string, min?: float, max?: float, unit?: string, help: string}>
     */
    public const FIELDS = [
        'gp_free' => [
            'key' => 'pricing.gp_free', 'type' => 'boolean', 'group' => 'pricing',
            'label' => 'โปรฯ GP ฟรีช่วงเปิดตัว',
            'help' => 'เปิดอยู่ = ไม่เก็บ GP ทุกสินค้า (ยกเว้นสินค้าที่แอดมินตั้ง GP รายสินค้า) ทั้งอีคอมเมิร์ซและตลาดสด',
        ],
        'gp_free_until' => [
            'key' => 'pricing.gp_free_until', 'type' => 'date', 'group' => 'pricing',
            'label' => 'วันสิ้นสุดโปรฯ GP ฟรี',
            'help' => 'ว่าง = ฟรีจนกว่าจะปิดเอง · ใส่วันที่ = ฟรีถึงสิ้นวันนั้น แล้วเก็บตามอัตรามาตรฐานอัตโนมัติ',
        ],
        'default_gp_rate' => [
            'key' => 'pricing.default_gp_rate', 'type' => 'float', 'group' => 'pricing', 'min' => 0, 'max' => 50, 'unit' => '%',
            'label' => 'อัตรา GP มาตรฐาน (อีคอมเมิร์ซ)',
            'help' => 'ใช้กับร้านที่ไม่มีแพ็กเกจ/อัตราเฉพาะ เมื่อไม่ได้อยู่ในโปรฯ GP ฟรี',
        ],
        'min_gp_rate' => [
            'key' => 'pricing.min_gp_rate', 'type' => 'float', 'group' => 'pricing', 'min' => 0, 'max' => 50, 'unit' => '%',
            'label' => 'อัตรา GP ขั้นต่ำ',
            'help' => 'แพ็กเกจร้านที่ตั้งต่ำกว่านี้จะถูกปรับขึ้นเป็นอัตรานี้ (ต้องไม่เกินอัตรามาตรฐาน)',
        ],
        'fresh_market_gp_rate' => [
            'key' => 'pricing.fresh_market_gp_rate', 'type' => 'float', 'group' => 'pricing', 'min' => 0, 'max' => 50, 'unit' => '%',
            'label' => 'อัตรา GP ตลาดสด',
            'help' => 'หักจากยอดขายของร้านตลาดสด (ไม่มี VAT/ค่าแนะนำ) เมื่อไม่ได้อยู่ในโปรฯ GP ฟรี',
        ],
        'referral_pool_percent' => [
            'key' => 'pricing.referral_pool_percent', 'type' => 'float', 'group' => 'pricing', 'min' => 0, 'max' => 30, 'unit' => '%',
            'label' => 'ค่าแนะนำ (เฉพาะเว็บ)',
            'help' => 'หักเข้ากองทุนผู้แนะนำเมื่อสินค้าไม่มี PV และระบบแนะนำเปิดอยู่ — ไม่แสดงในแอป',
        ],
        'vat_rate' => [
            'key' => 'pricing.vat_rate', 'type' => 'float', 'group' => 'pricing', 'min' => 0, 'max' => 20, 'unit' => '%',
            'label' => 'อัตรา VAT',
            'help' => 'ถอดจากราคาที่รวม VAT แล้ว เฉพาะร้านที่จดทะเบียน VAT',
        ],
        'official_shop_vat_registered' => [
            'key' => 'pricing.official_shop_vat_registered', 'type' => 'boolean', 'group' => 'pricing',
            'label' => 'ร้านทางการของแพลตฟอร์มจดทะเบียน VAT',
            'help' => 'เปิด = สินค้าร้านทางการถอด VAT ออกจากราคา',
        ],
        'rider_share_percent' => [
            'key' => 'rider.rider_share_percent', 'type' => 'float', 'group' => 'rider', 'min' => 0, 'max' => 100, 'unit' => '%',
            'label' => 'ส่วนแบ่งไรเดอร์จากค่าส่ง',
            'help' => 'ไรเดอร์ได้ % นี้ของค่าส่ง ที่เหลือเป็นรายได้แพลตฟอร์ม (ค่าส่งเริ่มต้น/ต่อกม. ตั้งที่หน้าตั้งค่าไรเดอร์)',
        ],
        'shipped_auto_release_days' => [
            'key' => SellerPayoutService::SETTING_SHIPPED_AUTO_RELEASE_DAYS, 'type' => 'integer', 'group' => 'money', 'min' => 1, 'max' => 60, 'unit' => 'วัน',
            'label' => 'ปล่อยเงินร้านอัตโนมัติหลังส่งพัสดุ',
            'help' => 'ส่งพัสดุแล้วลูกค้าไม่กดรับของ ระบบโอนรายได้ให้ร้านเองเมื่อครบจำนวนวันนี้',
        ],
    ];

    public function __construct(
        private readonly SettingAuditService $audit,
        private readonly PlatformShareAdvisor $advisor,
    ) {}

    /**
     * หน้าตั้งค่าส่วนแบ่งรายได้ & GP
     */
    public function index()
    {
        $engine = new PricingEngine;
        $current = $this->currentSettings($engine);
        $stats = $this->advisor->stats();
        $mlmEnabled = $engine->mlmEnabled();

        return view('admin.pricing.settings', [
            'settings' => $current,
            'fields' => self::FIELDS,
            'stats' => $stats,
            'mlmEnabled' => $mlmEnabled,
            'advice' => $this->advisor->advise($current + ['mlm_enabled' => $mlmEnabled], $stats),
            'history' => $this->audit->recent($this->keys(), $this->labelsByKey(), 15),
            'sample' => $this->simulation($current, [
                'price' => 100.0,
                'quantity' => 1,
                'pv' => 0.0,
                'cost' => null,
                'vat_registered' => false,
                'mlm_enabled' => $mlmEnabled,
                'distance_km' => 3.0,
            ], $engine),
            'formulaSteps' => PricingEngine::formulaStepsTh(),
            'updateUrl' => route('admin.pricing.settings.update'),
            'simulateUrl' => route('admin.pricing.simulate'),
            'riderSettingsUrl' => route('admin.riders.settings'),
            'pageTitle' => 'ส่วนแบ่งรายได้ & GP',
        ]);
    }

    /**
     * บันทึกค่าตั้ง — บันทึกเฉพาะค่าที่เปลี่ยน และเก็บประวัติทุกค่า
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_ERROR',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                    'data' => null,
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $engine = new PricingEngine;
        $current = $this->currentSettings($engine);
        $incoming = $this->normalizeInput((array) $request->input('settings', []), $current);
        $admin = $request->user();
        $changed = [];

        try {
            DB::transaction(function () use ($incoming, $current, $admin, $request, &$changed) {
                foreach (self::FIELDS as $field => $spec) {
                    if (! array_key_exists($field, $incoming)) {
                        continue;
                    }

                    $new = $incoming[$field];
                    if ($this->sameValue($current[$field], $new, $spec['type'])) {
                        continue;
                    }

                    $setting = Setting::set($spec['key'], $this->storeValue($new, $spec['type']), $this->storeType($spec['type']), $spec['group']);

                    $this->audit->record($admin, $setting, $current[$field], $new, $spec['label'], $request->ip());

                    $changed[] = [
                        'field' => $field,
                        'label' => $spec['label'],
                        'old' => $current[$field],
                        'new' => $new,
                    ];
                }
            });
        } catch (\Throwable $e) {
            Log::error('PricingSettings: save failed', [
                'admin_id' => $admin?->id,
                'error' => $e->getMessage(),
            ]);

            $message = 'บันทึกค่าตั้งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'code' => 'SAVE_FAILED', 'message' => $message, 'data' => null], 500);
            }

            return back()->withInput()->with('error', $message);
        }

        $message = $changed === []
            ? 'ไม่มีค่าที่เปลี่ยนแปลง'
            : 'บันทึกส่วนแบ่งรายได้ & GP แล้ว ('.count($changed).' รายการ)';

        if ($request->expectsJson()) {
            $fresh = $this->currentSettings(new PricingEngine);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'changed' => $changed,
                    'settings' => $fresh,
                    'history' => $this->audit->recent($this->keys(), $this->labelsByKey(), 15)->values(),
                ],
            ]);
        }

        return redirect()->route('admin.pricing.settings')->with('success', $message);
    }

    /**
     * ตัวจำลองสดจากค่าร่างในฟอร์ม (ยังไม่บันทึก) — ตอบ JSON
     */
    public function simulate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'pv' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'vat_registered' => ['nullable', 'boolean'],
            'mlm_enabled' => ['nullable', 'boolean'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings' => ['nullable', 'array'],
        ], [
            'price.required' => 'กรุณาใส่ราคาตัวอย่าง',
            'price.numeric' => 'ราคาตัวอย่างต้องเป็นตัวเลข',
            'price.max' => 'ราคาตัวอย่างสูงเกินไป',
            'price.min' => 'ราคาตัวอย่างต้องไม่ติดลบ',
            'quantity.*' => 'จำนวนต้องเป็นจำนวนเต็ม 1–999',
            'pv.*' => 'PV ต้องเป็นตัวเลขไม่ติดลบ',
            'cost.*' => 'ต้นทุนต้องเป็นตัวเลขไม่ติดลบ',
            'distance_km.*' => 'ระยะทางต้องอยู่ระหว่าง 0–100 กม.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
                'data' => null,
            ], 422);
        }

        $engine = new PricingEngine;
        $current = $this->currentSettings($engine);
        // ค่าร่างจากฟอร์ม — ค่าที่ผิดรูปแบบถูกตัดทิ้ง (ใช้ค่าปัจจุบันแทน) ไม่ทำให้ตัวจำลองพัง
        $draft = $this->normalizeInput((array) $request->input('settings', []), $current, lenient: true);
        $settings = array_merge($current, $draft);

        try {
            $result = $this->simulation($settings, [
                'price' => (float) $request->input('price'),
                'quantity' => (int) ($request->input('quantity') ?: 1),
                'pv' => (float) ($request->input('pv') ?: 0),
                'cost' => $request->filled('cost') ? (float) $request->input('cost') : null,
                'vat_registered' => $request->boolean('vat_registered'),
                'mlm_enabled' => $request->has('mlm_enabled') ? $request->boolean('mlm_enabled') : $engine->mlmEnabled(),
                'distance_km' => $request->filled('distance_km') ? (float) $request->input('distance_km') : 3.0,
            ], $engine);
        } catch (\Throwable $e) {
            Log::warning('PricingSettings: simulate failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'SIMULATE_FAILED',
                'message' => 'คำนวณตัวอย่างไม่สำเร็จ กรุณาตรวจตัวเลขอีกครั้ง',
                'data' => null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'คำนวณตัวอย่างแล้ว',
            'data' => $result,
        ]);
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * ค่าปัจจุบันทุกฟิลด์ (ชนิดข้อมูลจริง)
     *
     * @return array<string, mixed>
     */
    public function currentSettings(?PricingEngine $engine = null): array
    {
        $engine ??= new PricingEngine;

        $freshRaw = $engine->setting(PricingEngine::KEY_FRESH_MARKET_GP_RATE, null);
        $freshRate = is_numeric($freshRaw)
            ? (float) $freshRaw
            : (float) $engine->setting(PricingEngine::KEY_FRESH_PLATFORM_FEE, PricingEngine::DEFAULT_FRESH_MARKET_GP_RATE);

        $until = trim((string) ($engine->setting(PricingEngine::KEY_GP_FREE_UNTIL, '') ?? ''));
        if ($until !== '') {
            try {
                $until = \Illuminate\Support\Carbon::parse($until)->format('Y-m-d');
            } catch (\Throwable) {
                $until = '';
            }
        }

        $releaseDays = Setting::get(SellerPayoutService::SETTING_SHIPPED_AUTO_RELEASE_DAYS, SellerPayoutService::DEFAULT_SHIPPED_AUTO_RELEASE_DAYS);

        return [
            'gp_free' => filter_var($engine->setting(PricingEngine::KEY_GP_FREE, false), FILTER_VALIDATE_BOOLEAN),
            'gp_free_until' => $until,
            'default_gp_rate' => round($engine->defaultGpRate(), 2),
            'min_gp_rate' => round($engine->minGpRate(), 2),
            'fresh_market_gp_rate' => round(max(0.0, min(100.0, $freshRate)), 2),
            'referral_pool_percent' => round($engine->referralPoolPercent(), 2),
            'vat_rate' => round($engine->vatRate(), 2),
            'official_shop_vat_registered' => $engine->officialShopVatRegistered(),
            'rider_share_percent' => round((new DeliveryFeeCalculator)->floatSetting('rider.rider_share_percent'), 2),
            'shipped_auto_release_days' => is_numeric($releaseDays) ? max(1, (int) $releaseDays) : SellerPayoutService::DEFAULT_SHIPPED_AUTO_RELEASE_DAYS,
        ];
    }

    /**
     * กฎตรวจค่า + ข้อความไทย
     */
    private function validator(array $input): \Illuminate\Validation\Validator
    {
        $rules = ['settings' => ['required', 'array']];
        $messages = ['settings.required' => 'ไม่พบค่าที่ต้องการบันทึก', 'settings.array' => 'รูปแบบข้อมูลไม่ถูกต้อง'];

        foreach (self::FIELDS as $field => $spec) {
            $name = "settings.{$field}";
            $rules[$name] = match ($spec['type']) {
                'boolean' => ['nullable', 'boolean'],
                'date' => ['nullable', 'date_format:Y-m-d'],
                'integer' => ['nullable', 'integer', 'between:'.$spec['min'].','.$spec['max']],
                default => ['nullable', 'numeric', 'between:'.$spec['min'].','.$spec['max']],
            };
            $unit = isset($spec['unit']) ? ' '.$spec['unit'] : '';
            $messages["{$name}.between"] = $spec['label'].' ต้องอยู่ระหว่าง '.($spec['min'] ?? 0).' ถึง '.($spec['max'] ?? 0).$unit;
            $messages["{$name}.numeric"] = $spec['label'].' ต้องเป็นตัวเลข';
            $messages["{$name}.integer"] = $spec['label'].' ต้องเป็นจำนวนเต็ม';
            $messages["{$name}.boolean"] = $spec['label'].' ไม่ถูกต้อง';
            $messages["{$name}.date_format"] = $spec['label'].' ต้องเป็นวันที่รูปแบบ ปปปป-ดด-วว';
        }

        $validator = Validator::make($input, $rules, $messages);

        $validator->after(function ($v) use ($input) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->currentSettings();
            $data = (array) ($input['settings'] ?? []);
            $value = fn (string $field) => array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== ''
                ? $data[$field]
                : $current[$field];

            if ((float) $value('min_gp_rate') > (float) $value('default_gp_rate')) {
                $v->errors()->add('settings.min_gp_rate', 'อัตรา GP ขั้นต่ำต้องไม่เกินอัตรา GP มาตรฐาน');
            }

            $gpFree = array_key_exists('gp_free', $data)
                ? filter_var($data['gp_free'], FILTER_VALIDATE_BOOLEAN)
                : (bool) $current['gp_free'];
            $until = array_key_exists('gp_free_until', $data) ? trim((string) $data['gp_free_until']) : '';

            // วันที่ที่ส่งมาใหม่ต้องไม่ย้อนหลัง (ถ้าเปิดโปรฯ) — ค่าเดิมที่เลยวันแล้วไม่บังคับให้แก้
            if ($gpFree && $until !== '' && $until !== $current['gp_free_until']
                && \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $until)->endOfDay()->isPast()) {
                $v->errors()->add('settings.gp_free_until', 'วันสิ้นสุดโปรฯ ต้องเป็นวันนี้หรือหลังจากนี้');
            }
        });

        return $validator;
    }

    /**
     * แปลงค่าจากฟอร์มเป็นชนิดจริง (checkbox ใช้ hidden 0 + checkbox 1)
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $current
     * @return array<string, mixed> เฉพาะฟิลด์ที่ส่งมา
     */
    private function normalizeInput(array $input, array $current, bool $lenient = false): array
    {
        $out = [];

        foreach (self::FIELDS as $field => $spec) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $raw = $input[$field];

            switch ($spec['type']) {
                case 'boolean':
                    $out[$field] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'date':
                    $raw = trim((string) $raw);
                    if ($raw === '') {
                        $out[$field] = '';
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
                        $out[$field] = $raw;
                    } elseif (! $lenient) {
                        $out[$field] = $current[$field];
                    }
                    break;

                case 'integer':
                    if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                        break;
                    }
                    $out[$field] = (int) max($spec['min'], min($spec['max'], (int) $raw));
                    break;

                default:
                    if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                        break;
                    }
                    $out[$field] = round(max((float) $spec['min'], min((float) $spec['max'], (float) $raw)), 2);
            }
        }

        return $out;
    }

    private function sameValue(mixed $old, mixed $new, string $type): bool
    {
        return match ($type) {
            'boolean' => (bool) $old === (bool) $new,
            'integer' => (int) $old === (int) $new,
            'date' => trim((string) $old) === trim((string) $new),
            default => abs((float) $old - (float) $new) < 0.0001,
        };
    }

    private function storeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'date' => (string) $value,
            default => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0',
        };
    }

    private function storeType(string $type): string
    {
        return match ($type) {
            'date' => 'string',
            default => $type,
        };
    }

    /**
     * ผลจำลอง 1 ชุด: อีคอมเมิร์ซ / ตลาดสด / ค่าส่งไรเดอร์ + คำแนะนำ
     *
     * @param  array<string, mixed>  $s  ค่าตั้ง (ปัจจุบันหรือร่าง)
     * @param  array{price: float, quantity: int, pv: float, cost: ?float, vat_registered: bool, mlm_enabled: bool, distance_km: float}  $in
     * @return array<string, mixed>
     */
    private function simulation(array $s, array $in, PricingEngine $engine): array
    {
        $promoEnd = $this->advisor->parseEnd($s['gp_free_until'] ?: null);
        $promoActive = (bool) $s['gp_free'] && ($promoEnd === null || now()->lessThanOrEqualTo($promoEnd));

        $shopGp = $promoActive ? 0.0 : max((float) $s['default_gp_rate'], (float) $s['min_gp_rate']);
        $freshGp = $promoActive ? 0.0 : (float) $s['fresh_market_gp_rate'];
        $price = max(0.0, (float) $in['price']);
        $qty = max(1, (int) $in['quantity']);

        $shop = $engine->breakdown($price, $qty, [
            'gp_rate' => $shopGp,
            'pv' => $in['pv'],
            'mlm_enabled' => $in['mlm_enabled'],
            'referral_pool_percent' => (float) $s['referral_pool_percent'],
            'vat_registered' => $in['vat_registered'],
            'vat_rate' => (float) $s['vat_rate'],
            'cost_per_unit' => $in['cost'],
        ]);

        $fresh = $engine->breakdown($price, $qty, [
            'gp_rate' => $freshGp,
            'mlm_enabled' => false,
            'pv' => 0,
            'vat_registered' => false,
            'cost_per_unit' => $in['cost'],
        ]);

        // ค่าส่งไรเดอร์: ใช้ค่าตั้งไรเดอร์จริงทั้งหมด แต่ทับส่วนแบ่งด้วยค่าร่าง
        $base = new DeliveryFeeCalculator;
        $values = [];
        foreach (array_keys(DeliveryFeeCalculator::DEFAULTS) as $key) {
            $values[$key] = $base->setting($key);
        }
        $values['rider.rider_share_percent'] = (float) $s['rider_share_percent'];
        $rider = (new DeliveryFeeCalculator($values))->quoteForDistance(max(0.0, (float) $in['distance_km']));

        $gross = $shop->gross;
        $pct = fn (float $amount) => $gross > 0 ? round($amount / $gross * 100, 2) : 0.0;
        $sellerShown = max(0.0, $shop->seller_net);

        $split = [
            ['key' => 'seller_net', 'label' => 'ร้านค้าได้รับ', 'amount' => $sellerShown, 'percent' => $pct($sellerShown)],
            ['key' => 'gp', 'label' => 'GP แพลตฟอร์ม', 'amount' => $shop->gp_amount, 'percent' => $pct($shop->gp_amount)],
            ['key' => 'vat', 'label' => 'VAT (นำส่งสรรพากร)', 'amount' => $shop->vat_amount, 'percent' => $pct($shop->vat_amount)],
            ['key' => 'referral_pool', 'label' => 'ค่าแนะนำ (เว็บ)', 'amount' => $shop->referral_pool_amount, 'percent' => $pct($shop->referral_pool_amount)],
        ];

        $poolOverGp = round(max(0.0, $shop->referral_pool_amount - $shop->gp_amount), 2);

        return [
            'input' => [
                'price' => $price,
                'quantity' => $qty,
                'pv' => (float) $in['pv'],
                'cost' => $in['cost'],
                'vat_registered' => (bool) $in['vat_registered'],
                'mlm_enabled' => (bool) $in['mlm_enabled'],
                'distance_km' => round((float) $in['distance_km'], 2),
            ],
            'effective' => [
                'promo_active' => $promoActive,
                'promo_ends_at' => $promoEnd?->format('d/m/Y'),
                'shop_gp_rate' => round($shopGp, 2),
                'fresh_gp_rate' => round($freshGp, 2),
                'referral_pool_percent' => round((float) $s['referral_pool_percent'], 2),
                'vat_rate' => round((float) $s['vat_rate'], 2),
                'rider_share_percent' => round((float) $s['rider_share_percent'], 2),
            ],
            'shop' => $shop->toArray(),
            'fresh' => $fresh->toArray(),
            'rider' => $rider,
            'split' => $split,
            'pool_over_gp' => $poolOverGp,
            'advice' => $this->advisor->advise($s + ['mlm_enabled' => (bool) $in['mlm_enabled']], $this->advisor->stats()),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keys(): array
    {
        return array_values(array_map(fn ($spec) => $spec['key'], self::FIELDS));
    }

    /**
     * @return array<string, string>
     */
    private function labelsByKey(): array
    {
        $labels = [];
        foreach (self::FIELDS as $spec) {
            $labels[$spec['key']] = $spec['label'];
        }

        return $labels;
    }
}
