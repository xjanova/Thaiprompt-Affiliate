@extends('layouts.seller-v4')

@section('title', 'วางแผนราคา & กลยุทธ์')

@push('styles')
    @include('seller.partials.v4-styles')
    <style>
        .pp-card { position:relative; display:flex; flex-direction:column; gap:10px; padding:20px; min-width:0; }
        .pp-card.is-rec { box-shadow: var(--card-shadow), 0 0 0 2px var(--accent1); }
        .pp-rec-badge { position:absolute; top:-11px; left:18px; }
        .pp-price { font-family:var(--tp-font-num); font-size:clamp(26px,4.4vw,34px); font-weight:800; color:var(--deep1); line-height:1.1; }
        .pp-cmp th, .pp-cmp td { text-align:right; }
        .pp-cmp th:first-child, .pp-cmp td:first-child { text-align:left; }
        .pp-cmp tr.pp-strong td { font-weight:800; }
        .pp-range { width:100%; accent-color: var(--accent1); }
    </style>
@endpush

@php
    use App\Support\Seller\SellerUi;

    $gpRateText = isset($gpInfo['rate']) ? rtrim(rtrim(number_format((float) $gpInfo['rate'], 2), '0'), '.') . '%' : '—';
    $plannerConfig = [
        'planUrl' => route('seller.pricing.plan'),
        'applyUrl' => route('seller.pricing.apply'),
        'products' => $productOptions,
        'productId' => $product?->id,
        'cost' => $product && $product->cost_price !== null ? (float) $product->cost_price : '',
        'currentPrice' => $product ? (float) $product->price : '',
        'referralPool' => (bool) $mlmEnabled,
        'maxPrice' => $maxPrice,
    ];
@endphp

@section('content')
<div class="sv4-page" x-data="pricingPlanner(@js($plannerConfig))">

    <x-seller-v4.header title="วางแผนราคา & กลยุทธ์" subtitle="กรอกต้นทุนและเป้ากำไร ระบบเสนอราคา 3 แบบพร้อมเหตุผล — ตัวเลขชุดเดียวกับตอนแบ่งเงินจริง" icon="💡">
        <a href="{{ route('seller.products.index') }}" class="tp-btn tp-btn-sm">📦 สินค้าของร้าน</a>
    </x-seller-v4.header>

    {{-- ── สรุปค่าธรรมเนียมของร้าน ─────────────────────────── --}}
    <div class="sv4-stats">
        <div class="tp-card" style="padding:16px 17px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                <span style="font-size:12px; color:var(--ink2); font-weight:700;">🔒 ค่า GP ของร้าน</span>
                @if($gpPromoActive)
                    <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::OK) }}">🎉 ฟรี GP ช่วงเปิดตัว</span>
                @endif
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px; color:{{ ($gpInfo['rate'] ?? 0) > 0 ? 'var(--ink)' : SellerUi::OK }};" x-text="gpText || @js($gpRateText)">{{ $gpRateText }}</div>
            <div class="sv4-hint" style="margin-top:2px;" x-text="gpLabel || @js($gpInfo['label_th'] ?? '')">{{ $gpInfo['label_th'] ?? '' }}</div>
            @if($gpPromoActive && $gpPromoEndsAt)
                <div class="sv4-hint">โปรฯ ถึงวันที่ {{ $gpPromoEndsAt->format('d/m/Y') }}</div>
            @endif
        </div>
        <div class="tp-card" style="padding:16px 17px;">
            <span style="font-size:12px; color:var(--ink2); font-weight:700;">🧾 VAT ของร้าน</span>
            <div style="font-size:17px; font-weight:800; margin-top:8px;">{{ $vatRegistered ? 'จดทะเบียน VAT' : 'ไม่ได้จด VAT' }}</div>
            <div class="sv4-hint">{{ $vatRegistered ? 'ถอด VAT 7/107 ออกจากยอดขาย' : 'ไม่หัก VAT' }} · <a href="{{ route('seller.store.settings') }}#tax" class="sv4-link">ตั้งค่า</a></div>
        </div>
        <div class="tp-card" style="padding:16px 17px;">
            <span style="font-size:12px; color:var(--ink2); font-weight:700;">⚖️ ราคาคุ้มทุน</span>
            <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px;" x-text="result && result.breakeven_price !== null ? money(result.breakeven_price) : '—'">—</div>
            <div class="sv4-hint">กำไร 0 บาท หลังหักค่าธรรมเนียมทั้งหมด</div>
        </div>
        <div class="tp-card" style="padding:16px 17px;">
            <span style="font-size:12px; color:var(--ink2); font-weight:700;">📈 กำไรสูงสุดที่เป็นไปได้</span>
            <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px;" x-text="result ? pct(result.max_margin_percent) : '—'">—</div>
            <div class="sv4-hint">เพดาน % กำไรหลังหัก GP/VAT/ค่าแนะนำ</div>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ── ข้อมูลที่ใช้วางแผน ───────────────────────────── --}}
        <form class="tp-card" style="padding:20px; flex:1 1 320px; min-width:0; display:flex; flex-direction:column; gap:14px;"
              @submit.prevent="runPlan(true)">
            <div class="sv4-h2">🧾 ข้อมูลสินค้า</div>

            <div>
                <label for="pp_product" class="sv4-label">เลือกสินค้า (ไม่บังคับ)</label>
                <select id="pp_product" class="tp-input" x-model="productId" @change="pickProduct()">
                    <option value="">— วางแผนสินค้าใหม่ —</option>
                    <template x-for="p in products" :key="p.id">
                        <option :value="String(p.id)" x-text="p.name + (p.sku ? ' (' + p.sku + ')' : '') + ' · ฿' + Number(p.price).toLocaleString('th-TH')" :selected="String(p.id) === String(productId)"></option>
                    </template>
                </select>
                <div class="sv4-hint">เลือกแล้วระบบเติมต้นทุน/ราคาปัจจุบัน ใช้อัตรา GP และค่าแนะนำของสินค้านั้น และเทียบราคากลางของหมวดให้</div>
            </div>

            <div class="sv4-grid" style="grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));">
                <div>
                    <label for="pp_cost" class="sv4-label">ต้นทุนต่อชิ้น (บาท) <span class="req">*</span></label>
                    <input id="pp_cost" type="number" min="0" step="0.01" inputmode="decimal" class="tp-input tp-num" x-model="cost" placeholder="เช่น 120">
                </div>
                <div>
                    <label for="pp_competitor" class="sv4-label">ราคาคู่แข่ง (บาท)</label>
                    <input id="pp_competitor" type="number" min="0" step="0.01" inputmode="decimal" class="tp-input tp-num" x-model="competitor" placeholder="ไม่บังคับ">
                </div>
            </div>

            <div>
                <label for="pp_margin" class="sv4-label">เป้ากำไร (% ของราคาขาย): <span class="tp-num" style="color:var(--deep1);" x-text="margin + '%'"></span></label>
                <input id="pp_margin" type="range" min="0" max="90" step="1" class="tp-range" x-model="margin">
                <div class="sv4-hint">ตัวอย่าง 20% = ขาย 100 บาท เหลือกำไร 20 บาท หลังหักต้นทุนและค่าธรรมเนียม</div>
            </div>

            <div>
                <label for="pp_volume" class="sv4-label">ยอดขายที่คาดต่อเดือน (ชิ้น)</label>
                <input id="pp_volume" type="number" min="0" step="1" inputmode="numeric" class="tp-input tp-num" x-model="volume" placeholder="ไม่บังคับ — ใช้ประมาณกำไรรายเดือน">
            </div>

            <div>
                <span class="sv4-label">วิธีจัดส่ง</span>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px;">
                    <template x-for="opt in deliveryOptions" :key="opt.key">
                        <button type="button" class="tp-btn tp-btn-sm" :class="delivery === opt.key && 'tp-btn-primary'" @click="delivery = opt.key" x-text="opt.label"></button>
                    </template>
                </div>
            </div>

            <div class="sv4-grid" style="grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));" x-show="delivery !== 'none'" x-cloak>
                <div>
                    <label for="pp_dfee" class="sv4-label">ค่าส่งต่อเที่ยว (บาท)</label>
                    <input id="pp_dfee" type="number" min="0" step="1" class="tp-input tp-num" x-model="deliveryFee" placeholder="ว่าง = ประมาณให้">
                </div>
                <div x-show="delivery === 'rider'">
                    <label for="pp_km" class="sv4-label">ระยะส่งเฉลี่ย (กม.)</label>
                    <input id="pp_km" type="number" min="0" max="100" step="0.5" class="tp-input tp-num" x-model="distance" placeholder="3">
                </div>
            </div>

            <label class="sv4-well" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                <span class="sv4-switch"><input type="checkbox" x-model="referralPool"><span></span></span>
                <span style="min-width:0;">
                    <span style="font-weight:800; font-size:13px;">รวมค่าแนะนำสินค้า</span>
                    <span class="sv4-pill tp-pill-soft" style="margin-left:4px;">เฉพาะเว็บ</span>
                    <span class="sv4-hint" style="display:block; margin-top:2px;">หักค่าแนะนำ (PV) ให้ผู้ที่ช่วยแนะนำสินค้าของคุณ{{ $mlmEnabled ? '' : ' — ตอนนี้แพลตฟอร์มยังไม่เปิดระบบนี้ ใช้จำลองได้' }}</span>
                </span>
            </label>

            <details class="sv4-details sv4-well">
                <summary><span style="font-weight:800; font-size:13px;">⚙️ ตัวเลือกเพิ่มเติม</span><i class="fas fa-chevron-down sv4-chev"></i></summary>
                <div style="display:flex; flex-direction:column; gap:12px; margin-top:12px;">
                    <div>
                        <label for="pp_fixed" class="sv4-label">ค่าใช้จ่ายคงที่ต่อเดือน (บาท)</label>
                        <input id="pp_fixed" type="number" min="0" step="1" class="tp-input tp-num" x-model="fixedCost" placeholder="ค่าแพ็กเกจ ค่าเช่า ค่าโฆษณา">
                        <div class="sv4-hint">ใช้หาจำนวนชิ้นที่ต้องขายต่อเดือนให้คุ้มทุน</div>
                    </div>
                    <div>
                        <label for="pp_current" class="sv4-label">ราคาปัจจุบัน (บาท)</label>
                        <input id="pp_current" type="number" min="0" step="0.01" class="tp-input tp-num" x-model="currentPrice" placeholder="ถ้ามี ระบบประเมินให้">
                    </div>
                </div>
            </details>

            <button type="submit" class="tp-btn tp-btn-primary sv4-btn-block" style="height:46px; font-size:14px;" :disabled="loading">
                <span x-show="!loading">✨ วางแผนราคา</span>
                <span x-show="loading" x-cloak>กำลังคำนวณ…</span>
            </button>
            <div class="sv4-err" x-show="error" x-text="error" role="alert"></div>
        </form>

        {{-- ── ผลลัพธ์ ───────────────────────────────────────── --}}
        <div style="flex:2 1 520px; min-width:0; display:flex; flex-direction:column; gap:18px;">

            <template x-if="!result && !loading">
                <div class="tp-card">
                    <x-seller-v4.empty icon="💡" title="กรอกต้นทุนเพื่อเริ่มวางแผน" text="ระบบจะเสนอราคา 3 กลยุทธ์ (เจาะตลาด / สมดุล / พรีเมียม) พร้อมกำไรต่อชิ้น จุดคุ้มทุน และคำแนะนำ" />
                </div>
            </template>

            <template x-if="result">
                <div style="display:flex; flex-direction:column; gap:18px;">

                    {{-- คำเตือน --}}
                    <template x-if="result.warnings && result.warnings.length">
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <template x-for="w in result.warnings" :key="w.code">
                                <div class="sv4-note" style="--c:var(--tp-bad, #d9534f);" x-text="'⚠️ ' + w.message"></div>
                            </template>
                        </div>
                    </template>

                    {{-- คำแนะนำหลัก --}}
                    <div class="sv4-note" style="--c:var(--accent1); font-size:13px;">
                        <b>🏆 แนะนำ: <span x-text="recommendedName()"></span></b> — <span x-text="result.recommended_reason_th"></span>
                    </div>

                    {{-- การ์ด 3 กลยุทธ์ --}}
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px; padding-top:8px;">
                        <template x-for="s in result.strategies" :key="s.key">
                            <div class="tp-card pp-card" :class="s.key === result.recommended && 'is-rec'">
                                <span class="sv4-pill tp-pill-gold pp-rec-badge" x-show="s.key === result.recommended" style="color:var(--tp-on-accent, #fff);">⭐ แนะนำ</span>
                                <div class="sv4-row">
                                    <div style="font-weight:800; font-size:15px;" x-text="strategyIcon(s.key) + ' ' + s.name_th"></div>
                                    <span class="sv4-pill tp-pill-soft" x-show="s.vs_competitor_percent !== null"
                                          x-text="(s.vs_competitor_percent > 0 ? '+' : '') + Number(s.vs_competitor_percent).toFixed(1) + '% จากคู่แข่ง'"></span>
                                </div>
                                <div class="pp-price" x-text="s.price !== null ? money(s.price) : '—'"></div>
                                <div style="font-size:12px; color:var(--ink2); line-height:1.5;" x-text="s.description_th"></div>
                                <div class="sv4-well" style="padding:10px 12px;">
                                    <div class="sv4-kv"><span>กำไร/ชิ้น</span><span class="tp-num" :style="'color:' + ((s.profit_per_unit || 0) < 0 ? 'var(--tp-bad, #d9534f)' : 'var(--tp-ok, #5aa07e)')" x-text="s.profit_per_unit !== null ? money(s.profit_per_unit) : '—'"></span></div>
                                    <div class="sv4-kv"><span>% กำไร</span><span class="tp-num" x-text="s.margin_percent !== null ? pct(s.margin_percent) : '—'"></span></div>
                                    <div class="sv4-kv"><span>ร้านได้สุทธิ/ชิ้น</span><span class="tp-num" x-text="s.seller_net_per_unit !== null ? money(s.seller_net_per_unit) : '—'"></span></div>
                                    <div class="sv4-kv" x-show="s.breakeven_units !== null && fixedCost > 0"><span>ขายให้คุ้มค่าคงที่</span><span class="tp-num" x-text="s.breakeven_units + ' ชิ้น/เดือน'"></span></div>
                                    <template x-if="s.monthly">
                                        <div class="sv4-kv"><span>กำไร/เดือน (≈<span x-text="s.monthly.volume"></span> ชิ้น)</span><span class="tp-num" x-text="money(s.monthly.profit)"></span></div>
                                    </template>
                                </div>
                                <div style="font-size:11.5px; color:var(--ink2); line-height:1.5;" x-text="s.fit_th"></div>
                                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:auto;">
                                    <button type="button" class="tp-btn tp-btn-sm" @click="toggleDetail(s.key)" x-text="detail === s.key ? 'ซ่อนรายละเอียด' : 'ดูการแบ่งเงิน'"></button>
                                    <button type="button" class="tp-btn tp-btn-sm" :class="s.key === result.recommended && 'tp-btn-primary'"
                                            :disabled="s.price === null" @click="askApply(s)">✅ ใช้ราคานี้</button>
                                </div>
                                <template x-if="detail === s.key && s.breakdown">
                                    <div class="sv4-well" style="padding:10px 12px;">
                                        <template x-for="line in s.breakdown.lines" :key="line.key">
                                            <div class="sv4-kv" style="font-size:12px;">
                                                <span x-text="line.label_th"></span>
                                                <span class="tp-num" :style="lineStyle(line)" x-text="money(line.amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- ตารางเทียบการแบ่งเงินเต็ม --}}
                    <div class="tp-card" style="padding:0; overflow:hidden;">
                        <div style="padding:16px 20px;">
                            <div class="sv4-h2">📊 เทียบการแบ่งเงินต่อ 1 ชิ้น</div>
                            <div class="sv4-sub">ทุกบรรทัดคำนวณด้วย PricingEngine ชุดเดียวกับตอนโอนเงินให้ร้าน</div>
                        </div>
                        <div class="sv4-table-wrap">
                            <table class="sv4-table pp-cmp">
                                <thead>
                                    <tr>
                                        <th>รายการ</th>
                                        <template x-for="s in result.strategies" :key="'h' + s.key">
                                            <th x-text="s.name_th" :style="s.key === result.recommended ? 'color:var(--deep1);' : ''"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in compareRows" :key="row.key">
                                        <tr :class="row.strong && 'pp-strong'">
                                            <td x-text="row.label"></td>
                                            <template x-for="s in result.strategies" :key="row.key + s.key">
                                                <td class="tp-num" :style="row.style ? row.style(s) : ''" x-text="row.value(s)"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ราคาปัจจุบัน / ชุดสินค้า / การส่ง / ราคาตลาด --}}
                    <div class="sv4-grid-2">
                        <template x-if="result.current">
                            <div class="tp-card" style="padding:18px;">
                                <div class="sv4-h2">🔎 ราคาปัจจุบัน</div>
                                <div class="pp-price" style="font-size:24px; margin-top:6px;" x-text="money(result.current.price)"></div>
                                <div class="sv4-kv"><span>กำไร/ชิ้น</span><span class="tp-num" :style="'color:' + ((result.current.profit_per_unit || 0) < 0 ? 'var(--tp-bad, #d9534f)' : 'var(--tp-ok, #5aa07e)')" x-text="result.current.profit_per_unit !== null ? money(result.current.profit_per_unit) : '—'"></span></div>
                                <div class="sv4-kv"><span>% กำไร</span><span class="tp-num" x-text="result.current.margin_percent !== null ? pct(result.current.margin_percent) : '—'"></span></div>
                            </div>
                        </template>
                        <template x-if="result.bundle">
                            <div class="tp-card" style="padding:18px;">
                                <div class="sv4-h2">🎁 ขายเป็นชุด</div>
                                <div class="pp-price" style="font-size:24px; margin-top:6px;" x-text="money(result.bundle.price)"></div>
                                <div class="sv4-sub" x-text="'ชุด ' + result.bundle.quantity + ' ชิ้น · เฉลี่ยชิ้นละ ' + money(result.bundle.price_per_unit) + ' · ลด ' + pct(result.bundle.discount_percent)"></div>
                                <div class="sv4-kv"><span>กำไรต่อชุด</span><span class="tp-num" x-text="money(result.bundle.profit) + ' (' + pct(result.bundle.margin_percent) + ')'"></span></div>
                            </div>
                        </template>
                        <template x-if="result.delivery && result.delivery.mode !== 'none'">
                            <div class="tp-card" style="padding:18px;">
                                <div class="sv4-h2" x-text="result.delivery.mode === 'rider' ? '🛵 ค่าส่งไรเดอร์' : '📦 ค่าส่งพัสดุ'"></div>
                                <div class="pp-price" style="font-size:24px; margin-top:6px;" x-text="money(result.delivery.estimated_fee)"></div>
                                <div class="sv4-sub" x-text="result.delivery.fee_basis_th"></div>
                                <template x-if="result.delivery.free_delivery_threshold">
                                    <div class="sv4-kv"><span>ส่งฟรีเมื่อซื้อครบ</span><span class="tp-num" x-text="money(result.delivery.free_delivery_threshold) + ' (~' + result.delivery.units_for_free_delivery + ' ชิ้น)'"></span></div>
                                </template>
                            </div>
                        </template>
                        <template x-if="result.market && result.market.count > 0">
                            <div class="tp-card" style="padding:18px;">
                                <div class="sv4-h2">🏷️ ราคาในหมวดเดียวกัน</div>
                                <div class="sv4-sub" x-text="'จากสินค้าร้านอื่น ' + result.market.count + ' รายการ'"></div>
                                <div class="sv4-kv"><span>ต่ำสุด – สูงสุด</span><span class="tp-num" x-text="money(result.market.min) + ' – ' + money(result.market.max)"></span></div>
                                <div class="sv4-kv"><span>ราคากลาง</span><span class="tp-num" x-text="money(result.market.median)"></span></div>
                                <div class="sv4-kv"><span>ช่วงนิยม (25–75%)</span><span class="tp-num" x-text="money(result.market.p25) + ' – ' + money(result.market.p75)"></span></div>
                                <button type="button" class="tp-btn tp-btn-sm" style="margin-top:8px;" @click="competitor = result.market.median; runPlan(true)">ใช้ราคากลางเป็นราคาคู่แข่ง</button>
                            </div>
                        </template>
                    </div>

                    {{-- เทียบ GP ตามแพ็กเกจ --}}
                    <template x-if="result.gp_comparison && result.gp_comparison.length">
                        <div class="tp-card" style="padding:0; overflow:hidden;">
                            <div style="padding:16px 20px;">
                                <div class="sv4-h2">📦 กำไรที่ราคาสมดุล ถ้าใช้แพ็กเกจร้านอื่น</div>
                                <div class="sv4-sub">{{ $gpPromoActive ? 'ตอนนี้ไม่เก็บ GP (โปรฯ เปิดตัว) — ตารางนี้คืออัตราหลังหมดโปรฯ ใช้ประกอบการตัดสินใจ' : 'เทียบกำไรต่อชิ้นกับค่าแพ็กเกจก่อนเปลี่ยน' }}</div>
                            </div>
                            <div class="sv4-table-wrap">
                                <table class="sv4-table pp-cmp">
                                    <thead><tr><th>แพ็กเกจ</th><th>GP</th><th>กำไร/ชิ้น</th><th>% กำไร</th><th>ต่างจากตอนนี้</th></tr></thead>
                                    <tbody>
                                        <template x-for="row in result.gp_comparison" :key="row.key + row.gp_rate">
                                            <tr>
                                                <td x-text="row.label"></td>
                                                <td class="tp-num" x-text="pct(row.gp_rate)"></td>
                                                <td class="tp-num" x-text="money(row.profit_per_unit)"></td>
                                                <td class="tp-num" x-text="pct(row.margin_percent)"></td>
                                                <td class="tp-num" :style="'color:' + (row.extra_profit_per_unit > 0 ? 'var(--tp-ok, #5aa07e)' : (row.extra_profit_per_unit < 0 ? 'var(--tp-bad, #d9534f)' : 'var(--ink2)'))"
                                                    x-text="(row.extra_profit_per_unit > 0 ? '+' : '') + money(row.extra_profit_per_unit)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    {{-- คำแนะนำภาษาไทย --}}
                    <template x-if="result.recommendations && result.recommendations.length">
                        <div class="tp-card" style="padding:20px;">
                            <div class="sv4-h2">💬 คำแนะนำสำหรับร้านคุณ</div>
                            <ol style="margin:12px 0 0; padding-left:20px; display:flex; flex-direction:column; gap:8px; font-size:13px; line-height:1.65;">
                                <template x-for="(tip, i) in result.recommendations" :key="i">
                                    <li x-text="tip"></li>
                                </template>
                            </ol>
                        </div>
                    </template>
                </div>
            </template>

            {{-- สูตรคำนวณ (พับได้) --}}
            <details class="tp-card sv4-details" style="padding:18px 20px;">
                <summary><span class="sv4-h2">📐 ระบบคิดเงินอย่างไร (สูตรคำนวณ)</span><i class="fas fa-chevron-down sv4-chev"></i></summary>
                <ol style="margin:14px 0 0; padding-left:0; list-style:none; display:flex; flex-direction:column; gap:8px; font-size:13px; line-height:1.65;">
                    @foreach($formulaSteps as $step)
                        <li class="sv4-well" style="padding:10px 14px;">{{ $step }}</li>
                    @endforeach
                </ol>
                <div class="sv4-hint" style="margin-top:10px;">
                    อัตรา GP ของร้านคุณตอนนี้: <b>{{ $gpRateText }}</b> ({{ $gpInfo['label_th'] ?? '-' }}) ·
                    "% กำไร" ในหน้านี้ = กำไรสุทธิ ÷ ราคาขาย × 100 (ไม่ใช่ % บวกเพิ่มจากต้นทุน) ·
                    ราคาทั้ง 3 แผนปัดให้ลงท้ายด้วยเลขที่ลูกค้าชอบ (5/9)
                </div>
            </details>
        </div>
    </div>

    {{-- กล่องยืนยัน "ใช้ราคานี้กับสินค้า" --}}
    <template x-teleport="body">
        <div x-show="confirm.open" x-cloak x-transition.opacity @keydown.escape.window="confirm.open = false" class="grid"
             style="position:fixed; inset:0; z-index:120; place-items:center; padding:16px; background:rgba(0,0,0,.45); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px);">
            <div class="tp-card" role="dialog" aria-modal="true" @click.outside="confirm.open = false" style="width:100%; max-width:440px; padding:22px;">
                <div style="font-weight:800; font-size:16px;">✅ ใช้ราคานี้กับสินค้า?</div>
                <template x-if="productId">
                    <div style="font-size:13px; color:var(--ink2); margin-top:8px; line-height:1.65;">
                        เปลี่ยนราคา “<b style="color:var(--ink);" x-text="productName()"></b>” จาก
                        <b class="tp-num" style="color:var(--ink);" x-text="money(selectedProduct() ? selectedProduct().price : 0)"></b> เป็น
                        <b class="tp-num" style="color:var(--deep1);" x-text="money(confirm.price)"></b>
                        (กลยุทธ์<span x-text="confirm.name"></span>) ลูกค้าจะเห็นราคาใหม่ทันที
                    </div>
                </template>
                <template x-if="!productId">
                    <div style="margin-top:10px;">
                        <div style="font-size:13px; color:var(--ink2); line-height:1.6;">เลือกสินค้าที่จะใช้ราคา <b class="tp-num" style="color:var(--deep1);" x-text="money(confirm.price)"></b></div>
                        <select class="tp-input" style="margin-top:10px;" x-model="confirm.productId" aria-label="เลือกสินค้า">
                            <option value="">— เลือกสินค้า —</option>
                            <template x-for="p in products" :key="'c' + p.id">
                                <option :value="String(p.id)" x-text="p.name + ' · ฿' + Number(p.price).toLocaleString('th-TH')"></option>
                            </template>
                        </select>
                        <div class="sv4-hint" x-show="products.length === 0">ร้านยังไม่มีสินค้า — <a href="{{ route('seller.products.create') }}" class="sv4-link">เพิ่มสินค้า</a> แล้วกลับมาวางแผนอีกครั้ง</div>
                    </div>
                </template>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap;">
                    <button type="button" class="tp-btn" @click="confirm.open = false">ยกเลิก</button>
                    <button type="button" class="tp-btn tp-btn-primary" :disabled="applying || !(productId || confirm.productId)" @click="applyPrice()">
                        <span x-show="!applying">บันทึกราคาใหม่</span>
                        <span x-show="applying" x-cloak>กำลังบันทึก…</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
/**
 * หน้าวางแผนราคา & กลยุทธ์ — เรียก API ของระบบ (StrategyAdvisor/PricingEngine) ไม่คำนวณสูตรเองในเบราว์เซอร์
 */
function pricingPlanner(cfg) {
    const fmtMoney = (n) => '฿' + (Number(n) || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return {
        products: cfg.products || [],
        productId: cfg.productId ? String(cfg.productId) : '',
        cost: cfg.cost,
        competitor: '',
        margin: 20,
        volume: '',
        fixedCost: '',
        currentPrice: cfg.currentPrice,
        delivery: 'none',
        deliveryFee: '',
        distance: '',
        referralPool: !!cfg.referralPool,
        deliveryOptions: [
            { key: 'none', label: 'ไม่ส่ง/นัดรับ' },
            { key: 'rider', label: '🛵 ไรเดอร์' },
            { key: 'parcel', label: '📦 พัสดุ' }
        ],
        result: null,
        loading: false,
        error: '',
        detail: null,
        gpText: '',
        gpLabel: '',
        applying: false,
        confirm: { open: false, price: 0, strategy: 'custom', name: '', productId: '' },
        _timer: null,
        _seq: 0,

        init() {
            ['cost', 'competitor', 'margin', 'volume', 'fixedCost', 'currentPrice', 'delivery', 'deliveryFee', 'distance', 'referralPool']
                .forEach((k) => this.$watch(k, () => this.schedule()));
            if (this.hasInput()) this.runPlan(false);
        },

        get compareRows() {
            const b = (s, k) => (s.breakdown ? s.breakdown[k] : null);
            const neg = (v) => (v === null || v === undefined) ? '—' : (Number(v) > 0 ? '−' + fmtMoney(v) : fmtMoney(0));
            const bad = () => 'color:var(--tp-bad, #d9534f);';
            return [
                { key: 'price', label: 'ราคาขาย', strong: true, value: (s) => s.price !== null ? fmtMoney(s.price) : '—' },
                { key: 'gp', label: 'ค่า GP แพลตฟอร์ม', value: (s) => neg(b(s, 'gp_amount')), style: bad },
                { key: 'vat', label: 'VAT (7/107)', value: (s) => neg(b(s, 'vat_amount')), style: bad },
                { key: 'pool', label: 'ค่าแนะนำสินค้า', value: (s) => neg(b(s, 'referral_pool_amount')), style: bad },
                { key: 'fee', label: 'ค่าธรรมเนียมรับชำระ', value: (s) => neg(b(s, 'payment_fee')), style: bad },
                { key: 'net', label: 'ร้านได้รับสุทธิ', strong: true, value: (s) => s.seller_net_per_unit !== null ? fmtMoney(s.seller_net_per_unit) : '—' },
                { key: 'cost', label: 'ต้นทุน', value: (s) => neg(b(s, 'cost_total')), style: bad },
                { key: 'profit', label: 'กำไรต่อชิ้น', strong: true, value: (s) => s.profit_per_unit !== null ? fmtMoney(s.profit_per_unit) : '—',
                  style: (s) => 'color:' + ((s.profit_per_unit || 0) < 0 ? 'var(--tp-bad, #d9534f)' : 'var(--tp-ok, #5aa07e)') + ';' },
                { key: 'margin', label: '% กำไร', value: (s) => s.margin_percent !== null ? Number(s.margin_percent).toFixed(1) + '%' : '—' },
                { key: 'be', label: 'จุดคุ้มทุน (ชิ้น/เดือน)', value: (s) => s.breakeven_units !== null ? String(s.breakeven_units) : '—' }
            ];
        },

        hasInput() {
            return (parseFloat(this.cost) > 0) || (parseFloat(this.competitor) > 0);
        },

        schedule() {
            clearTimeout(this._timer);
            if (!this.hasInput()) return;
            this._timer = setTimeout(() => this.runPlan(false), 600);
        },

        selectedProduct() {
            return this.products.find((p) => String(p.id) === String(this.productId)) || null;
        },

        productName() {
            const p = this.selectedProduct();
            return p ? p.name : '';
        },

        pickProduct() {
            const p = this.selectedProduct();
            if (p) {
                if (p.cost !== null && p.cost !== undefined) this.cost = p.cost;
                this.currentPrice = p.price;
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('product_id', p.id);
                    window.history.replaceState({}, '', url);
                } catch (e) {}
            } else {
                this.currentPrice = '';
            }
            this.schedule();
        },

        async runPlan(explicit) {
            if (!this.hasInput()) {
                if (explicit) this.error = 'กรุณากรอกต้นทุนต่อชิ้น หรือราคาคู่แข่งอย่างน้อยหนึ่งอย่าง';
                return;
            }
            const seq = ++this._seq;
            this.loading = true;
            this.error = '';
            const num = (v) => { const n = parseFloat(v); return isFinite(n) ? n : null; };
            const body = {
                cost: num(this.cost) || 0,
                target_margin_percent: num(this.margin) ?? 20,
                delivery: this.delivery,
                referral_pool: this.referralPool ? 1 : 0
            };
            if (this.productId) body.product_id = parseInt(this.productId, 10);
            if (num(this.competitor)) body.competitor_price = num(this.competitor);
            if (num(this.volume) !== null) body.monthly_volume = Math.max(0, Math.round(num(this.volume)));
            if (num(this.fixedCost)) body.fixed_monthly_cost = num(this.fixedCost);
            if (num(this.currentPrice)) body.current_price = num(this.currentPrice);
            if (this.delivery !== 'none' && num(this.deliveryFee) !== null) body.delivery_fee = num(this.deliveryFee);
            if (this.delivery === 'rider' && num(this.distance) !== null) body.avg_distance_km = num(this.distance);

            try {
                const token = document.querySelector('meta[name="csrf-token"]');
                const res = await fetch(cfg.planUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                    },
                    body: JSON.stringify(body)
                });
                const json = await res.json().catch(() => null);
                if (seq !== this._seq) return;
                if (!res.ok || !json || !json.success) {
                    this.error = this.errorText(json);
                    return;
                }
                this.result = json.data;
                if (json.data.gp) {
                    const r = Number(json.data.gp.rate);
                    this.gpText = isFinite(r) ? (Math.round(r * 100) / 100) + '%' : '';
                    this.gpLabel = json.data.gp.label_th || '';
                }
            } catch (e) {
                if (seq === this._seq) this.error = 'เชื่อมต่อไม่ได้ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่';
            } finally {
                if (seq === this._seq) this.loading = false;
            }
        },

        errorText(json) {
            if (json && json.errors) {
                const first = Object.values(json.errors)[0];
                if (Array.isArray(first) && first.length) return first[0];
            }
            return (json && json.message) ? json.message : 'วางแผนราคาไม่สำเร็จ กรุณาลองใหม่';
        },

        recommendedName() {
            if (!this.result) return '';
            const s = (this.result.strategies || []).find((x) => x.key === this.result.recommended);
            return s ? s.name_th : '';
        },

        strategyIcon(key) {
            return { penetration: '🚀', balanced: '⚖️', premium: '👑' }[key] || '💡';
        },

        toggleDetail(key) {
            this.detail = this.detail === key ? null : key;
        },

        askApply(s) {
            if (s.price === null) return;
            this.confirm = { open: true, price: s.price, strategy: s.key, name: s.name_th, productId: '' };
        },

        async applyPrice() {
            const productId = this.productId || this.confirm.productId;
            if (!productId || this.applying) return;
            this.applying = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]');
                const res = await fetch(cfg.applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                    },
                    body: JSON.stringify({ product_id: parseInt(productId, 10), price: this.confirm.price, strategy: this.confirm.strategy })
                });
                const json = await res.json().catch(() => null);
                if (!res.ok || !json || !json.success) {
                    window.showNotification && window.showNotification(this.errorText(json), 'error');
                    return;
                }
                const p = this.products.find((x) => String(x.id) === String(productId));
                if (p) p.price = json.data.price;
                if (String(productId) === String(this.productId)) this.currentPrice = json.data.price;
                this.confirm.open = false;
                window.showNotification && window.showNotification(json.message, 'success');
            } catch (e) {
                window.showNotification && window.showNotification('เชื่อมต่อไม่ได้ กรุณาลองใหม่', 'error');
            } finally {
                this.applying = false;
            }
        },

        money(n) {
            if (n === null || n === undefined) return '—';
            const v = Number(n) || 0;
            return (v < 0 ? '−' : '') + fmtMoney(Math.abs(v));
        },

        pct(n) {
            if (n === null || n === undefined) return '—';
            return (Math.round((Number(n) || 0) * 10) / 10) + '%';
        },

        lineStyle(line) {
            if (line.kind === 'deduction') return 'color:var(--tp-bad, #d9534f);';
            if (line.kind === 'income') return 'color:var(--tp-ok, #5aa07e);';
            if (line.kind === 'result') return 'font-weight:800;';
            return 'color:var(--ink2);';
        }
    };
}
</script>
@endpush
