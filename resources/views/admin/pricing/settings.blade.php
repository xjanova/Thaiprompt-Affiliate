{{--
    หน้าแอดมิน "ส่วนแบ่งรายได้ & GP" (ธีม V4 นวลทองคำ)

    ตัวแปรจาก Admin\PricingSettingsController::index():
      $settings          ค่าปัจจุบัน [field => value] (ชนิดจริง)
      $fields            PricingSettingsController::FIELDS [field => {key, type, group, label, min?, max?, unit?, help}]
      $stats             ตัวเลขประกอบ {completed_orders, shop_orders, fresh_orders, shops, fresh_sellers, riders}
      $mlmEnabled        ระบบแนะนำ (เว็บ) เปิดอยู่หรือไม่
      $advice            PlatformShareAdvisor::advise() {items[], milestone{}, promo_active, promo_ends_at}
      $history           ประวัติการแก้ [{at, at_human, user, key, label, old, new}]
      $sample            ผลจำลองเริ่มต้น (ราคา 100 บาท) รูปแบบเดียวกับ JSON ของ admin.pricing.simulate
      $formulaSteps      ขั้นตอนสูตรภาษาไทย
      $updateUrl         POST admin.pricing.settings.update ฟิลด์ settings[<field>] (boolean = hidden 0 + checkbox 1)
      $simulateUrl       POST admin.pricing.simulate (JSON)
      $riderSettingsUrl  หน้าตั้งค่าไรเดอร์ (ค่าส่งเริ่มต้น/ต่อกม./COD/การกระจายงาน)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ส่วนแบ่งรายได้ & GP')

@push('styles')
<style>
    .ps-page {
        --ps-ok: var(--tp-ok, #4f9e7e);
        --ps-bad: var(--tp-bad, #d9534f);
        --ps-warn: var(--tp-warn, #d99a2b);
        --ps-info: var(--tp-info, #5b87c9);
        --ps-on: var(--tp-on-accent, #fff);
    }
    .ps-label { display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px; }
    .ps-help { font-size:11.5px; color:var(--ink2); margin-top:6px; line-height:1.55; }
    .ps-err { font-size:12px; color:var(--ps-bad); margin-top:6px; font-weight:600; }
    .ps-switch { position:relative; display:inline-flex; align-items:center; gap:12px; cursor:pointer; min-height:44px; user-select:none; }
    .ps-switch input { position:absolute; opacity:0; width:1px; height:1px; }
    .ps-track { width:54px; height:30px; flex:none; border-radius:20px; position:relative; background:var(--surf); box-shadow:var(--inset-sm); transition:background .2s ease; }
    .ps-knob { position:absolute; top:3px; left:3px; width:24px; height:24px; border-radius:50%; background:var(--sl); box-shadow:var(--raise); transition:transform .2s ease; }
    .ps-switch input:checked + .ps-track { background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .ps-switch input:checked + .ps-track .ps-knob { transform:translateX(24px); }
    .ps-switch input:focus-visible + .ps-track { outline:2px solid var(--accent1); outline-offset:2px; }
    .ps-num-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .ps-num-row .tp-input { width:120px; flex:none; text-align:right; }
    .ps-num-row .tp-range { flex:1 1 160px; min-width:140px; }
    .ps-seg { display:flex; height:22px; border-radius:12px; overflow:hidden; box-shadow:var(--inset-sm); background:var(--surf); }
    .ps-seg > i { display:block; height:100%; transition:width .35s ease; }
    .ps-dot { width:10px; height:10px; border-radius:50%; flex:none; display:inline-block; }
    .ps-line { display:flex; justify-content:space-between; align-items:baseline; gap:10px; padding:8px 0; border-bottom:1px dashed color-mix(in srgb, var(--ink2) 22%, transparent); font-size:13px; }
    .ps-line:last-child { border-bottom:0; }
    .ps-3d { background:linear-gradient(180deg, color-mix(in srgb, var(--sl) 55%, var(--surf)), var(--surf)); box-shadow:var(--card-shadow), inset 0 1px 0 color-mix(in srgb, var(--sl) 80%, transparent); }
    .ps-sticky-bar { position:sticky; bottom:12px; z-index:20; }
</style>
@endpush

@section('content')
<div class="ps-page" x-data="pricingSettings()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · การเงินแพลตฟอร์ม · ส่วนแบ่งรายได้</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ส่วนแบ่งรายได้ &amp; GP 💰</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px; max-width:680px;">
                ตั้งว่าเงินทุกออเดอร์แบ่งให้ร้านค้า ไรเดอร์ และแพลตฟอร์มอย่างไร — มีผลกับออเดอร์ใหม่ทันทีหลังบันทึก ออเดอร์เก่าใช้ตัวเลข ณ ตอนสั่งซื้อ
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            <span class="tp-pill" x-cloak x-show="result.effective.promo_active"
                  style="display:none; padding:8px 12px; font-size:12px; color:var(--ps-on); background:linear-gradient(135deg,var(--accent1),var(--accent2));">
                <i class="fas fa-gift"></i>
                <span x-text="result.effective.promo_ends_at ? ('โปรฯ GP ฟรี ถึง ' + result.effective.promo_ends_at) : 'โปรฯ GP ฟรี (ไม่มีกำหนดสิ้นสุด)'"></span>
            </span>
            <span class="tp-pill tp-pill-soft" x-cloak x-show="!result.effective.promo_active" style="display:none; padding:8px 12px; font-size:12px;">
                <i class="fas fa-percent"></i> <span x-text="'เก็บ GP ' + fmtRate(result.effective.shop_gp_rate) + '%'"></span>
            </span>
            <a href="{{ $riderSettingsUrl }}" class="tp-btn tp-btn-sm" style="min-height:44px;">
                <i class="fas fa-motorcycle" style="color:var(--accent2);"></i> ตั้งค่าไรเดอร์
            </a>
        </div>
    </div>

    {{-- ===== ข้อผิดพลาดจากการส่งฟอร์มแบบไม่มี JS ===== --}}
    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--ps-bad);">
            <div style="font-weight:700; color:var(--ps-bad); margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--ink);">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        <div class="tp-card ps-3d" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; font-size:18px;"><i class="fas fa-store"></i></div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;"><span x-text="fmtRate(result.effective.shop_gp_rate)">{{ rtrim(rtrim(number_format($sample['effective']['shop_gp_rate'], 2), '0'), '.') }}</span>%</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">GP อีคอมเมิร์ซที่ใช้ตอนนี้</div>
                </div>
            </div>
        </div>
        <div class="tp-card ps-3d" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; font-size:18px;"><i class="fas fa-carrot"></i></div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;"><span x-text="fmtRate(result.effective.fresh_gp_rate)">{{ rtrim(rtrim(number_format($sample['effective']['fresh_gp_rate'], 2), '0'), '.') }}</span>%</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">GP ตลาดสดที่ใช้ตอนนี้</div>
                </div>
            </div>
        </div>
        <div class="tp-card ps-3d" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; font-size:18px;"><i class="fas fa-motorcycle"></i></div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;"><span x-text="fmtRate(form.rider_share_percent)">{{ rtrim(rtrim(number_format($settings['rider_share_percent'], 2), '0'), '.') }}</span>%</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ไรเดอร์ได้จากค่าส่ง</div>
                </div>
            </div>
        </div>
        <div class="tp-card ps-3d" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; font-size:18px;"><i class="fas fa-flag-checkered"></i></div>
                <div style="flex:1; min-width:0;">
                    <div class="tp-num" style="font-size:22px; font-weight:800; line-height:1;">
                        {{ number_format($advice['milestone']['completed']) }}<span style="font-size:13px; color:var(--ink2); font-weight:600;"> / {{ number_format($advice['milestone']['target']) }}</span>
                    </div>
                    <div style="font-size:12px; color:var(--ink2); margin:3px 0 6px;">ออเดอร์สำเร็จ (เป้าก่อนเริ่มเก็บ GP)</div>
                    <div class="ps-seg" style="height:8px;">
                        <i style="width:{{ max(2, $advice['milestone']['percent']) }}%; background:linear-gradient(90deg,var(--accent1),var(--deep1));"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== เนื้อหาหลัก: ฟอร์ม (ซ้าย) + ตัวจำลอง/คำแนะนำ (ขวา) ===== --}}
    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ---------- ฟอร์มค่าตั้ง ---------- --}}
        <form method="POST" action="{{ $updateUrl }}" @submit.prevent="save()" novalidate
              style="flex:1 1 460px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            @csrf

            {{-- โปรโมชันเปิดตัว --}}
            <div class="tp-card" style="padding:20px; border-left:4px solid var(--accent1);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <div class="tp-tile" style="width:36px; height:36px; font-size:15px;"><i class="fas fa-gift"></i></div>
                    <div>
                        <div class="tp-section-h">โปรโมชันเปิดตัว: ไม่เก็บค่า GP</div>
                        <div style="font-size:11.5px; color:var(--ink2);">ดึงร้านเล็ก รถเข็น ตลาดนัด เข้าระบบโดยไม่มีค่าใช้จ่าย</div>
                    </div>
                </div>

                <label class="ps-switch">
                    <input type="hidden" name="settings[gp_free]" value="0">
                    <input type="checkbox" name="settings[gp_free]" value="1" x-model="form.gp_free" @checked($settings['gp_free'])>
                    <span class="ps-track"><span class="ps-knob"></span></span>
                    <span style="font-size:13.5px; font-weight:700;" x-text="form.gp_free ? 'เปิดโปรฯ GP ฟรีอยู่' : 'ปิดโปรฯ (เก็บ GP ตามอัตรามาตรฐาน)'">{{ $fields['gp_free']['label'] }}</span>
                </label>
                <div class="ps-help">{{ $fields['gp_free']['help'] }}</div>

                <div style="margin-top:14px;" x-show="form.gp_free" x-cloak>
                    <label class="ps-label" for="ps-gp-free-until">{{ $fields['gp_free_until']['label'] }}</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <input id="ps-gp-free-until" type="date" name="settings[gp_free_until]" x-model="form.gp_free_until"
                               value="{{ $settings['gp_free_until'] }}" class="tp-input tp-num" style="max-width:220px; min-height:44px;">
                        <button type="button" class="tp-btn tp-btn-sm" style="min-height:44px;" x-show="form.gp_free_until" @click="form.gp_free_until = ''">
                            <i class="fas fa-infinity"></i> ไม่มีกำหนด
                        </button>
                    </div>
                    <div class="ps-help">{{ $fields['gp_free_until']['help'] }}</div>
                    <div class="ps-err" x-show="errors.gp_free_until" x-text="errors.gp_free_until"></div>
                </div>
            </div>

            {{-- GP อีคอมเมิร์ซ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-bag-shopping" style="color:var(--accent2);"></i> GP อีคอมเมิร์ซ</div>
                <div style="font-size:11.5px; color:var(--ink2); margin-bottom:14px;">ลำดับอัตรา: ร้านทางการ = 0 → GP ที่แอดมินตั้งรายสินค้า → โปรฯ GP ฟรี → แพ็กเกจร้าน → อัตรามาตรฐาน (ไม่ต่ำกว่าขั้นต่ำ)</div>

                @foreach(['default_gp_rate', 'min_gp_rate'] as $field)
                    <div style="margin-bottom:16px;">
                        <label class="ps-label" for="ps-{{ $field }}">{{ $fields[$field]['label'] }}</label>
                        <div class="ps-num-row">
                            <input id="ps-{{ $field }}" type="number" step="0.01" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}"
                                   name="settings[{{ $field }}]" value="{{ $settings[$field] }}" x-model.number="form.{{ $field }}"
                                   class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                            <span class="tp-muted" style="font-weight:700;">{{ $fields[$field]['unit'] }}</span>
                            <input type="range" class="tp-range" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}" step="0.5"
                                   x-model.number="form.{{ $field }}" aria-label="{{ $fields[$field]['label'] }}">
                        </div>
                        <div class="ps-help">{{ $fields[$field]['help'] }}</div>
                        <div class="ps-err" x-show="errors.{{ $field }}" x-text="errors.{{ $field }}"></div>
                    </div>
                @endforeach
            </div>

            {{-- ตลาดสด + ไรเดอร์ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-basket-shopping" style="color:var(--accent2);"></i> ตลาดสด &amp; ไรเดอร์</div>

                @foreach(['fresh_market_gp_rate', 'rider_share_percent'] as $field)
                    <div style="margin-bottom:16px;">
                        <label class="ps-label" for="ps-{{ $field }}">{{ $fields[$field]['label'] }}</label>
                        <div class="ps-num-row">
                            <input id="ps-{{ $field }}" type="number" step="0.01" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}"
                                   name="settings[{{ $field }}]" value="{{ $settings[$field] }}" x-model.number="form.{{ $field }}"
                                   class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                            <span class="tp-muted" style="font-weight:700;">{{ $fields[$field]['unit'] }}</span>
                            <input type="range" class="tp-range" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}" step="{{ $field === 'rider_share_percent' ? 1 : 0.5 }}"
                                   x-model.number="form.{{ $field }}" aria-label="{{ $fields[$field]['label'] }}">
                        </div>
                        <div class="ps-help">{{ $fields[$field]['help'] }}</div>
                        <div class="ps-err" x-show="errors.{{ $field }}" x-text="errors.{{ $field }}"></div>
                    </div>
                @endforeach

                {{-- ลิงก์ไปหน้าตั้งค่าไรเดอร์ --}}
                <a href="{{ $riderSettingsUrl }}" class="tp-card tp-card-hover ps-3d"
                   style="display:flex; align-items:center; gap:12px; padding:14px 16px; text-decoration:none; color:var(--ink); border-radius:16px;">
                    <div class="tp-tile" style="width:40px; height:40px; font-size:16px;"><i class="fas fa-route"></i></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:13.5px;">ค่าส่งเริ่มต้น · ต่อกิโลเมตร · COD · การกระจายงาน</div>
                        <div style="font-size:11.5px; color:var(--ink2);">ตั้งค่าส่วนอื่นของระบบไรเดอร์ที่หน้าตั้งค่าไรเดอร์</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:var(--ink2);"></i>
                </a>
            </div>

            {{-- ภาษี + ค่าแนะนำ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-receipt" style="color:var(--accent2);"></i> ภาษี &amp; ค่าแนะนำ</div>

                @foreach(['vat_rate', 'referral_pool_percent'] as $field)
                    <div style="margin-bottom:16px;">
                        <label class="ps-label" for="ps-{{ $field }}">
                            {{ $fields[$field]['label'] }}
                            @if($field === 'referral_pool_percent')
                                <span class="tp-pill {{ $mlmEnabled ? 'tp-pill-gold' : 'tp-pill-soft' }}" style="margin-left:6px;">
                                    ระบบแนะนำ: {{ $mlmEnabled ? 'เปิด' : 'ปิด' }}
                                </span>
                            @endif
                        </label>
                        <div class="ps-num-row">
                            <input id="ps-{{ $field }}" type="number" step="0.01" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}"
                                   name="settings[{{ $field }}]" value="{{ $settings[$field] }}" x-model.number="form.{{ $field }}"
                                   class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                            <span class="tp-muted" style="font-weight:700;">{{ $fields[$field]['unit'] }}</span>
                            <input type="range" class="tp-range" min="{{ $fields[$field]['min'] }}" max="{{ $fields[$field]['max'] }}" step="0.5"
                                   x-model.number="form.{{ $field }}" aria-label="{{ $fields[$field]['label'] }}">
                        </div>
                        <div class="ps-help">{{ $fields[$field]['help'] }}</div>
                        <div class="ps-err" x-show="errors.{{ $field }}" x-text="errors.{{ $field }}"></div>
                    </div>
                @endforeach

                <label class="ps-switch">
                    <input type="hidden" name="settings[official_shop_vat_registered]" value="0">
                    <input type="checkbox" name="settings[official_shop_vat_registered]" value="1" x-model="form.official_shop_vat_registered" @checked($settings['official_shop_vat_registered'])>
                    <span class="ps-track"><span class="ps-knob"></span></span>
                    <span style="font-size:13.5px; font-weight:700;">{{ $fields['official_shop_vat_registered']['label'] }}</span>
                </label>
                <div class="ps-help">{{ $fields['official_shop_vat_registered']['help'] }}</div>
            </div>

            {{-- การโอนเงินให้ร้าน --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-money-bill-transfer" style="color:var(--accent2);"></i> การโอนรายได้ให้ร้าน</div>
                <label class="ps-label" for="ps-shipped_auto_release_days">{{ $fields['shipped_auto_release_days']['label'] }}</label>
                <div class="ps-num-row">
                    <input id="ps-shipped_auto_release_days" type="number" step="1" min="{{ $fields['shipped_auto_release_days']['min'] }}" max="{{ $fields['shipped_auto_release_days']['max'] }}"
                           name="settings[shipped_auto_release_days]" value="{{ $settings['shipped_auto_release_days'] }}" x-model.number="form.shipped_auto_release_days"
                           class="tp-input tp-num" style="min-height:44px;" inputmode="numeric">
                    <span class="tp-muted" style="font-weight:700;">{{ $fields['shipped_auto_release_days']['unit'] }}</span>
                </div>
                <div class="ps-help">{{ $fields['shipped_auto_release_days']['help'] }}</div>
                <div class="ps-err" x-show="errors.shipped_auto_release_days" x-text="errors.shipped_auto_release_days"></div>
            </div>

            {{-- แถบบันทึก --}}
            <div class="ps-sticky-bar">
                <div class="tp-card ps-3d" style="padding:12px 14px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
                    <div style="font-size:12.5px; color:var(--ink2);">
                        <span x-show="dirtyCount === 0">ยังไม่มีการเปลี่ยนแปลง</span>
                        <span x-show="dirtyCount > 0" x-cloak style="color:var(--deep1); font-weight:700;">
                            <i class="fas fa-pen"></i> แก้ไขแล้ว <span x-text="dirtyCount"></span> ค่า (ยังไม่บันทึก)
                        </span>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="tp-btn" style="min-height:44px;" @click="reset()" :disabled="dirtyCount === 0 || saving">
                            <i class="fas fa-rotate-left"></i> คืนค่าเดิม
                        </button>
                        <button type="submit" class="tp-btn tp-btn-primary" style="min-height:44px; padding:0 22px;" :disabled="saving">
                            <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง'">บันทึกการเปลี่ยนแปลง</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- ---------- ตัวจำลอง + คำแนะนำ ---------- --}}
        <div style="flex:1 1 380px; min-width:0; display:flex; flex-direction:column; gap:16px;">

            {{-- ตัวจำลอง --}}
            <div class="tp-card ps-3d" style="padding:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px;">
                    <div class="tp-section-h"><i class="fas fa-calculator" style="color:var(--accent1);"></i> ตัวจำลองการแบ่งเงิน</div>
                    <i class="fas fa-spinner fa-spin" x-show="simulating" x-cloak style="color:var(--ink2);"></i>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-bottom:12px;">คำนวณจากค่าในฟอร์ม (ยังไม่บันทึกก็เห็นผลทันที) ด้วยสูตรเดียวกับตอนตัดเงินจริง</div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:10px;">
                    <div>
                        <label class="ps-label" for="ps-sim-price" style="font-size:11.5px;">ราคาขาย (บาท)</label>
                        <input id="ps-sim-price" type="number" min="0" step="1" x-model.number="sim.price" class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                    </div>
                    <div>
                        <label class="ps-label" for="ps-sim-qty" style="font-size:11.5px;">จำนวน</label>
                        <input id="ps-sim-qty" type="number" min="1" max="999" step="1" x-model.number="sim.quantity" class="tp-input tp-num" style="min-height:44px;" inputmode="numeric">
                    </div>
                    <div>
                        <label class="ps-label" for="ps-sim-km" style="font-size:11.5px;">ระยะส่ง (กม.)</label>
                        <input id="ps-sim-km" type="number" min="0" max="100" step="0.5" x-model.number="sim.distance_km" class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                    </div>
                    <div>
                        <label class="ps-label" for="ps-sim-pv" style="font-size:11.5px;">PV ต่อชิ้น (เว็บ)</label>
                        <input id="ps-sim-pv" type="number" min="0" step="1" x-model.number="sim.pv" class="tp-input tp-num" style="min-height:44px;" inputmode="decimal">
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:4px 18px; margin-top:6px;">
                    <label class="ps-switch" style="font-size:12.5px;">
                        <input type="checkbox" x-model="sim.vat_registered">
                        <span class="ps-track"><span class="ps-knob"></span></span>
                        <span style="font-weight:600;">ร้านจด VAT</span>
                    </label>
                    <label class="ps-switch" style="font-size:12.5px;">
                        <input type="checkbox" x-model="sim.mlm_enabled">
                        <span class="ps-track"><span class="ps-knob"></span></span>
                        <span style="font-weight:600;">เปิดค่าแนะนำ (เว็บ)</span>
                    </label>
                </div>

                <div class="ps-err" x-show="simError" x-text="simError" x-cloak></div>

                {{-- แถบสัดส่วน --}}
                <div style="margin-top:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:8px;">
                        <div style="font-size:12.5px; font-weight:700;">อีคอมเมิร์ซ: ยอดขาย <span class="tp-num" x-text="'฿' + fmt(result.shop.gross)"></span></div>
                        <div class="tp-num" style="font-size:12px; color:var(--ink2);" x-text="'GP ' + fmtRate(result.shop.gp_rate) + '%'"></div>
                    </div>
                    <div class="ps-seg" role="img" aria-label="สัดส่วนการแบ่งเงินต่อออเดอร์">
                        <template x-for="part in result.split" :key="part.key">
                            <i :style="'width:' + Math.max(0, part.percent) + '%; background:' + colorFor(part.key)" :title="part.label + ' ' + fmt(part.amount) + ' บาท'"></i>
                        </template>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:10px;">
                        <template x-for="part in result.split" :key="'lg-' + part.key">
                            <div x-show="part.amount > 0 || part.key === 'seller_net'" class="flex" style="align-items:center; gap:6px; font-size:12px;">
                                <span class="ps-dot" :style="'background:' + colorFor(part.key)"></span>
                                <span x-text="part.label"></span>
                                <span class="tp-num" style="font-weight:700;" x-text="'฿' + fmt(part.amount)"></span>
                                <span class="tp-num" style="color:var(--ink2);" x-text="'(' + fmtRate(part.percent) + '%)'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- บรรทัดรายละเอียด --}}
                <div class="tp-inset-sm" style="margin-top:14px; padding:6px 14px; border-radius:14px;">
                    <template x-for="line in result.shop.lines" :key="line.key">
                        <div class="ps-line">
                            <span :style="line.kind === 'result' ? 'font-weight:800;' : (line.kind === 'info' ? 'color:var(--ink2);' : '')" x-text="line.label_th"></span>
                            <span class="tp-num" :style="'white-space:nowrap; font-weight:' + (line.kind === 'result' ? 800 : 600) + '; color:' + lineColor(line)" x-text="(line.amount < 0 ? '−฿' : '฿') + fmt(Math.abs(line.amount))"></span>
                        </div>
                    </template>
                </div>

                <template x-for="w in result.shop.warnings" :key="w.code">
                    <div class="tp-pill" style="margin-top:8px; padding:7px 11px; font-size:11.5px; white-space:normal; line-height:1.4; background:color-mix(in srgb, var(--ps-warn) 18%, transparent); color:var(--ink);">
                        <i class="fas fa-triangle-exclamation" style="color:var(--ps-warn);"></i> <span x-text="w.message"></span>
                    </div>
                </template>
                <div x-show="result.pool_over_gp > 0" x-cloak class="tp-pill" style="margin-top:8px; padding:7px 11px; font-size:11.5px; white-space:normal; line-height:1.4; background:color-mix(in srgb, var(--ps-bad) 14%, transparent); color:var(--ink);">
                    <i class="fas fa-people-arrows" style="color:var(--ps-bad);"></i>
                    <span x-text="'ค่าแนะนำเกินส่วน GP ' + fmt(result.pool_over_gp) + ' บาท — ร้านค้ารับภาระส่วนนี้ ควรจ่ายจาก GP แทน'"></span>
                </div>

                {{-- ตลาดสด + ไรเดอร์ --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; margin-top:16px;">
                    <div class="tp-card" style="padding:14px; border-radius:16px;">
                        <div style="font-size:12px; color:var(--ink2); font-weight:700;"><i class="fas fa-carrot"></i> ตลาดสด (ยอดเท่ากัน)</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; margin-top:6px;" x-text="'฿' + fmt(result.fresh.seller_net)"></div>
                        <div style="font-size:11.5px; color:var(--ink2);">ร้านได้รับ · GP <span class="tp-num" x-text="fmtRate(result.fresh.gp_rate) + '% = ฿' + fmt(result.fresh.gp_amount)"></span></div>
                    </div>
                    <div class="tp-card" style="padding:14px; border-radius:16px;">
                        <div style="font-size:12px; color:var(--ink2); font-weight:700;"><i class="fas fa-motorcycle"></i> ค่าส่ง <span class="tp-num" x-text="fmt(result.rider.distance_km) + ' กม.'"></span></div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; margin-top:6px;" x-text="'฿' + fmt(result.rider.total_fee)"></div>
                        <div style="font-size:11.5px; color:var(--ink2);">
                            ไรเดอร์ <span class="tp-num" style="color:var(--ps-ok); font-weight:700;" x-text="'฿' + fmt(result.rider.rider_earnings)"></span>
                            · แพลตฟอร์ม <span class="tp-num" x-text="'฿' + fmt(result.rider.platform_fee)"></span>
                        </div>
                        <div x-show="!result.rider.within_service_area" x-cloak style="font-size:11px; color:var(--ps-bad); margin-top:4px;">
                            เกินระยะส่งสูงสุด <span class="tp-num" x-text="fmt(result.rider.max_distance_km)"></span> กม.
                        </div>
                    </div>
                </div>
            </div>

            {{-- คำแนะนำ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-lightbulb" style="color:var(--accent1);"></i> คำแนะนำสำหรับแพลตฟอร์ม</div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <template x-for="(item, idx) in result.advice.items" :key="idx">
                        <div class="tp-inset-sm" style="display:flex; gap:12px; padding:12px 14px; border-radius:14px;" :style="'border-left:4px solid ' + levelColor(item.level)">
                            <div style="width:34px; height:34px; flex:none; border-radius:11px; display:grid; place-items:center;"
                                 :style="'background:color-mix(in srgb, ' + levelColor(item.level) + ' 16%, transparent); color:' + levelColor(item.level)">
                                <i class="fas" :class="item.icon"></i>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-weight:700; font-size:13px;" x-text="item.title"></div>
                                <div style="font-size:12px; color:var(--ink2); line-height:1.55; margin-top:2px;" x-text="item.message"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- สูตรคำนวณ --}}
            <div class="tp-card" style="padding:18px;" x-data="{ open: false }">
                <button type="button" class="tp-toggle-btn" style="min-height:44px;" @click="open = !open" :aria-expanded="open">
                    <span><i class="fas fa-square-root-variable"></i> สูตรคำนวณที่ระบบใช้</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div x-show="open" x-cloak class="flex" style="margin-top:12px; flex-direction:column; gap:6px; font-size:12.5px; color:var(--ink2); line-height:1.65;">
                    @foreach($formulaSteps as $step)
                        <div>{{ $step }}</div>
                    @endforeach
                </div>
            </div>

            {{-- ประวัติการแก้ไข --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-clock-rotate-left" style="color:var(--accent2);"></i> ประวัติการเปลี่ยนแปลง</div>
                <div x-show="history.length === 0" style="font-size:12.5px; color:var(--ink2); padding:10px 0;">ยังไม่มีการเปลี่ยนแปลงจากหน้านี้</div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <template x-for="(h, idx) in history" :key="idx">
                        <div class="tp-inset-sm" style="padding:10px 12px; border-radius:12px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; font-size:11.5px; color:var(--ink2);">
                                <span><i class="fas fa-user-shield"></i> <span x-text="h.user"></span></span>
                                <span class="tp-num" x-text="h.at"></span>
                            </div>
                            <div style="font-size:12.5px; margin-top:4px;">
                                <span style="font-weight:700;" x-text="h.label"></span>:
                                <span class="tp-num" style="color:var(--ink2); text-decoration:line-through;" x-text="h.old"></span>
                                <i class="fas fa-arrow-right" style="font-size:10px; color:var(--ink2);"></i>
                                <span class="tp-num" style="font-weight:800; color:var(--deep1);" x-text="h.new"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function pricingSettings() {
    return {
        form: @js($settings),
        original: @js($settings),
        sim: {
            price: 100,
            quantity: 1,
            pv: 0,
            distance_km: 3,
            vat_registered: false,
            mlm_enabled: @js((bool) $mlmEnabled),
        },
        result: @js($sample),
        history: @js($history->values()),
        errors: {},
        saving: false,
        simulating: false,
        simError: '',
        timer: null,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        init() {
            this.$watch('form', () => this.queueSimulate(), { deep: true });
            this.$watch('sim', () => this.queueSimulate(), { deep: true });
        },

        get dirtyCount() {
            let n = 0;
            for (const key of Object.keys(this.original)) {
                const a = this.original[key];
                const b = this.form[key];
                if (typeof a === 'number' || typeof b === 'number') {
                    if (Math.abs(Number(a) - Number(b)) > 0.0001 || (b === '' && a !== '')) n++;
                } else if (String(a ?? '') !== String(b ?? '')) {
                    n++;
                }
            }
            return n;
        },

        fmt(n) {
            return Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        fmtRate(n) {
            return Number(n || 0).toLocaleString('th-TH', { maximumFractionDigits: 2 });
        },

        colorFor(key) {
            return {
                seller_net: 'var(--ps-ok)',
                gp: 'linear-gradient(90deg, var(--accent1), var(--accent2))',
                vat: 'var(--ps-info)',
                referral_pool: 'var(--ps-bad)',
            }[key] || 'var(--ink2)';
        },

        lineColor(line) {
            if (line.kind === 'income') return 'var(--ink)';
            if (line.kind === 'deduction') return 'var(--ps-bad)';
            if (line.kind === 'result') return line.amount < 0 ? 'var(--ps-bad)' : 'var(--ps-ok)';
            return 'var(--ink2)';
        },

        levelColor(level) {
            return { good: 'var(--ps-ok)', warn: 'var(--ps-warn)', info: 'var(--ps-info)' }[level] || 'var(--ink2)';
        },

        queueSimulate() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.simulate(), 300);
        },

        async simulate() {
            this.simulating = true;
            this.simError = '';
            try {
                const res = await fetch(@js($simulateUrl), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({
                        price: this.sim.price === '' ? 0 : this.sim.price,
                        quantity: this.sim.quantity || 1,
                        pv: this.sim.pv || 0,
                        distance_km: this.sim.distance_km === '' ? 0 : this.sim.distance_km,
                        vat_registered: !!this.sim.vat_registered,
                        mlm_enabled: !!this.sim.mlm_enabled,
                        settings: this.form,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    this.simError = data.message || 'คำนวณตัวอย่างไม่สำเร็จ';
                    return;
                }
                this.result = data.data;
            } catch (e) {
                this.simError = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่';
            } finally {
                this.simulating = false;
            }
        },

        reset() {
            this.form = JSON.parse(JSON.stringify(this.original));
            this.errors = {};
        },

        notify(type, message) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            this.errors = {};
            try {
                const payload = {};
                for (const key of Object.keys(this.form)) {
                    const v = this.form[key];
                    payload[key] = typeof v === 'boolean' ? (v ? 1 : 0) : v;
                }
                const res = await fetch(@js($updateUrl), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ settings: payload }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 422 && data.errors) {
                    const mapped = {};
                    for (const [k, v] of Object.entries(data.errors)) {
                        mapped[k.replace('settings.', '')] = Array.isArray(v) ? v[0] : v;
                    }
                    this.errors = mapped;
                    this.notify('error', data.message || 'กรุณาตรวจค่าที่กรอก');
                    return;
                }
                if (!res.ok || !data.success) {
                    this.notify('error', data.message || 'บันทึกไม่สำเร็จ กรุณาลองใหม่');
                    return;
                }
                this.form = data.data.settings;
                this.original = JSON.parse(JSON.stringify(data.data.settings));
                this.history = data.data.history || this.history;
                this.notify('success', data.message);
            } catch (e) {
                this.notify('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
