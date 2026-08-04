@extends('layouts.seller-v4')

@section('title', 'แดชบอร์ดร้านค้า - ' . ($store->store_name ?? 'ร้านค้าของคุณ'))

@php
    // ── เตรียมข้อมูลกราฟรายได้ 12 เดือน ───────────────────────────────
    // ธีม V4 ใช้กราฟแท่ง CSS (.tp-bars) แทน Chart.js — ไม่ต้องโหลดสคริปต์จาก CDN
    // $monthlyRevenue จากคอนโทรลเลอร์เป็น collection ของ ['month' => 'Jan 2026', 'total' => 0]
    $chartRows = collect($monthlyRevenue ?? []);
    $chartMax = (float) ($chartRows->max('total') ?: 0);
    $chartSum = (float) $chartRows->sum('total');

    // สีสถานะออเดอร์ (map เป็น hex ใช้ใน V4 แทนคลาส tailwind แบบไดนามิก)
    $orderStatus = [
        'pending' => ['รอดำเนินการ', '#e0a52e'],
        'processing' => ['กำลังจัดเตรียม', '#5689b8'],
        'completed' => ['สำเร็จ', '#5aa07e'],
        'cancelled' => ['ยกเลิก', '#d9534f'],
    ];

    // เมนูด่วน: [อีโมจิ, ป้าย, ลิงก์, เปิดแท็บใหม่ไหม]
    $quickActions = [
        ['➕', 'เพิ่มสินค้า', route('seller.products.create'), false],
        ['📋', 'ดูออเดอร์', route('seller.orders.index'), false],
        ['🎨', 'แต่งร้าน', route('seller.store.settings'), false],
        ['📊', 'รายงาน', route('seller.analytics.index'), false],
        ['📢', 'การตลาด', route('seller.marketing.index'), false],
        ['🌐', 'หน้าร้าน', $store->store_url, true],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวร้าน (Hero) ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px 26px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                @if($store->store_logo)
                    <img src="{{ $store->logo_url }}" alt="โลโก้ {{ $store->store_name }}"
                         style="width:62px; height:62px; border-radius:18px; object-fit:cover; box-shadow:var(--inset-sm); flex:none;">
                @else
                    <span class="tp-tile" style="width:62px; height:62px; border-radius:18px; font-size:28px; flex:none;">🏪</span>
                @endif
                <div style="min-width:0;">
                    <h1 style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--ink); overflow-wrap:anywhere;">{{ $store->store_name }}</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ยินดีต้อนรับสู่ระบบจัดการร้านค้า</div>
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:7px; margin-top:8px;">
                        <span class="tp-pill tp-pill-soft tp-num">{{ $store->store_slug }}</span>
                        @if($store->is_verified)
                            <span class="tp-pill" style="color:#fff; background:#5aa07e;">✓ ยืนยันแล้ว</span>
                        @endif
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; align-items:flex-start; gap:9px;">
                @if($package)
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                        <span class="tp-pill tp-pill-gold" style="font-weight:800;">{{ $package->display_name }}</span>
                        @if($store->subscription_status === 'trial')
                            <span class="tp-pill" style="color:#fff; background:#5689b8;">
                                ทดลองใช้: {{ $store->trial_ends_at ? $store->trial_ends_at->diffForHumans() : 'ไม่ระบุ' }}
                            </span>
                        @endif
                    </div>
                @else
                    <a href="{{ route('seller.packages') }}" class="tp-btn tp-btn-sm">เลือกแพ็คเกจ</a>
                @endif

                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <a href="{{ route('seller.store.settings') }}" class="tp-btn tp-btn-sm">⚙️ ตั้งค่าร้าน</a>
                    <a href="{{ $store->store_url }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">🌐 ดูหน้าร้าน</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── คำขวัญ TP-Affiliate (สลับอัตโนมัติ) ───────────────── --}}
    @if(!empty($slogans) && count($slogans) > 0)
    <div class="tp-card" x-data="sloganCarousel()" x-init="init()" style="padding:18px 20px;">
        <div style="display:flex; align-items:flex-start; gap:14px;">
            <span class="tp-tile" style="width:50px; height:50px; border-radius:15px; font-size:23px; flex:none;"
                  x-text="currentSlogan.icon || '🇹🇭'"></span>
            <div style="flex:1; min-width:0;">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:7px;">
                    <span>💝</span>
                    <span x-text="currentSlogan.category_name || 'พันธสัญญาจากเรา'"></span>
                </div>
                {{-- คอลัมน์จริงคือ content / author (ไม่ใช่ text) — ตรวจกับ Slogan model แล้ว --}}
                <p style="margin:7px 0 0; font-size:14px; line-height:1.65; color:var(--ink); overflow-wrap:anywhere;"
                   x-text="currentSlogan.content || 'เราช่วยคุณ คุณช่วยเรา ไทยช่วยไทย'"></p>
                <p x-show="currentSlogan.author"
                   style="margin:6px 0 0; font-size:12px; color:var(--ink2); font-style:italic;"
                   x-text="'— ' + (currentSlogan.author || '')"></p>

                <div style="display:flex; align-items:center; gap:12px; margin-top:12px; flex-wrap:wrap;">
                    {{-- จุดบอกลำดับ กดเลือกได้ --}}
                    <div style="display:flex; gap:6px;">
                        <template x-for="(s, i) in slogans" :key="i">
                            <button type="button" @click="goTo(i)"
                                    :aria-label="'คำขวัญที่ ' + (i + 1)"
                                    :style="i === index
                                        ? 'width:20px; height:7px; border-radius:99px; border:0; cursor:pointer; background:var(--deep1);'
                                        : 'width:7px; height:7px; border-radius:99px; border:0; cursor:pointer; background:color-mix(in srgb, var(--ink2) 40%, transparent);'"></button>
                        </template>
                    </div>
                    <button type="button" @click="toggleAuto()" class="tp-btn tp-btn-sm"
                            x-text="autoPlay ? '⏸ หยุดสลับ' : '▶ สลับอัตโนมัติ'"></button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── สถิติหลัก 4 ใบ ────────────────────────────────────── --}}
    @php
        $mainStats = [
            ['รายได้วันนี้', '฿'.number_format($todayRevenue ?? 0, 0), '💰', '#5aa07e', 'อัพเดทล่าสุดเมื่อสักครู่'],
            ['รายได้ทั้งหมด', '฿'.number_format($totalRevenue ?? 0, 0), '📊', '#5689b8', number_format($completedSales ?? 0).' ออเดอร์สำเร็จ'],
            ['คำสั่งซื้อรอดำเนินการ', number_format($pendingSales ?? 0), '⏳', '#e0a52e', 'จากทั้งหมด '.number_format($totalSales ?? 0)],
            ['สินค้าทั้งหมด', number_format($totalProducts ?? 0), '📦', '#8b6bb8', ($activeProducts ?? 0).' ใช้งาน'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px;">
        @foreach($mainStats as $i => [$label, $value, $icon, $color, $hint])
            <div class="tp-card" style="padding:18px; min-width:0;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:17px; flex:none; background:color-mix(in srgb, {{ $color }} 18%, transparent);">{{ $icon }}</span>
                </div>
                <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:10px; color:{{ $color }}; overflow-wrap:anywhere;">{{ $value }}</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:3px;">
                    {{-- การ์ดใบที่ 2 โชว์อัตราเติบโต / ใบที่ 4 เตือนสต็อกใกล้หมด --}}
                    @if($i === 1 && ($salesGrowth ?? 0) != 0)
                        <span style="color:{{ $salesGrowth > 0 ? '#5aa07e' : '#d9534f' }}; font-weight:700;">
                            {{ $salesGrowth > 0 ? '▲' : '▼' }} {{ number_format(abs($salesGrowth), 1) }}%
                        </span>
                        · {{ $hint }}
                    @elseif($i === 3 && ($lowStockProducts ?? 0) > 0)
                        <span style="color:#e0a52e; font-weight:700;">⚠️ {{ $lowStockProducts }} ใกล้หมด</span>
                        · {{ $outOfStockProducts ?? 0 }} หมดสต็อก
                    @else
                        {{ $hint }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── สถิติรอง 4 ใบ ─────────────────────────────────────── --}}
    @php
        $subStats = [
            ['ผู้เยี่ยมชม (30 วัน)', number_format($totalVisitors ?? 0), '👥'],
            ['อัตราการแปลง', number_format($conversionRate ?? 0, 1).'%', '📈'],
            ['คะแนนร้าน', number_format($store->rating_average ?? 0, 1), '⭐'],
            ['รีวิวทั้งหมด', number_format($store->rating_count ?? 0), '💬'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
        @foreach($subStats as [$label, $value, $icon])
            <div class="tp-card" style="padding:15px; display:flex; align-items:center; gap:11px; min-width:0;">
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; flex:none;">{{ $icon }}</span>
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2);">{{ $label }}</div>
                    <div class="tp-num" style="font-size:19px; font-weight:800; overflow-wrap:anywhere;">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ การดำเนินการด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
            @foreach($quickActions as [$emoji, $label, $href, $blank])
                <a href="{{ $href }}" @if($blank) target="_blank" rel="noopener" @endif
                   class="tp-card" style="padding:16px 12px; text-align:center; text-decoration:none; color:var(--ink); display:block;">
                    <div style="font-size:26px;">{{ $emoji }}</div>
                    <div style="font-size:12.5px; font-weight:700; margin-top:6px;">{{ $label }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── กราฟรายได้ 12 เดือน (CSS bars — ไม่ใช้ Chart.js) ──── --}}
    <div class="tp-card" style="padding:20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
            <div>
                <div class="tp-section-h">📈 รายได้ · รายเดือน</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">12 เดือนล่าสุด</div>
            </div>
            <span class="tp-pill tp-pill-gold tp-num">฿{{ number_format($chartSum, 0) }}</span>
        </div>
        @if($chartRows->isNotEmpty() && $chartMax > 0)
        <div class="tp-bars">
            @foreach($chartRows as $row)
                @php
                    $val = (float) ($row['total'] ?? 0);
                    // 'Aug 2026' → 'Aug' (Str::limit 6 ตัวจะได้ "Aug 20" ปีขาดครึ่ง อ่านแล้วงง)
                    $monthLabel = strtok((string) ($row['month'] ?? ''), ' ');
                @endphp
                <div class="col" title="{{ $row['month'] ?? '' }}: ฿{{ number_format($val, 2) }}">
                    <div class="stack" style="height:100%;">
                        <span class="bar a" style="height:{{ max(3, (int) round(($val / $chartMax) * 100)) }}%;"></span>
                    </div>
                    <span class="lbl">{{ $monthLabel }}</span>
                </div>
            @endforeach
        </div>
        @else
            <div style="text-align:center; color:var(--ink2); padding:46px 0; font-size:13px;">ยังไม่มีรายได้ในช่วง 12 เดือนที่ผ่านมา</div>
        @endif
    </div>

    {{-- ── คำสั่งซื้อล่าสุด ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div>
                <div class="tp-section-h">📋 คำสั่งซื้อล่าสุด</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">รายการที่เข้ามาล่าสุด</div>
            </div>
            <a href="{{ route('seller.orders.index') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>

        @if($recentOrders->isEmpty())
            <div style="text-align:center; padding:52px 20px;">
                <div style="font-size:52px; opacity:.55;">🛍️</div>
                <div style="margin-top:10px; font-weight:700;">ยังไม่มีคำสั่งซื้อ</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">คำสั่งซื้อจะแสดงที่นี่เมื่อมีลูกค้าสั่งซื้อสินค้าจากร้านคุณ</div>
                <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-sm" style="margin-top:14px; display:inline-block;">เพิ่มสินค้าตัวแรก</a>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">เลขที่คำสั่งซื้อ</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">ลูกค้า</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">ยอดรวม</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:center;">สถานะ</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            @php
                                [$statusLabel, $statusColor] = $orderStatus[$order->status] ?? [$order->status, 'var(--ink2)'];
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <a href="{{ route('seller.orders.show', $order) }}" class="tp-num" style="color:var(--deep1); font-weight:700; text-decoration:none;">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td style="padding:12px 16px;">
                                    <div style="display:flex; align-items:center; gap:9px; min-width:0;">
                                        <span class="tp-tile" style="width:30px; height:30px; border-radius:9px; font-size:12px; font-weight:800; flex:none;">
                                            {{ mb_substr($order->user->name ?? 'U', 0, 1) }}
                                        </span>
                                        <div style="min-width:0;">
                                            <div style="font-size:12.5px; font-weight:600; overflow-wrap:anywhere;">{{ $order->user->name ?? 'ไม่ระบุ' }}</div>
                                            <div style="font-size:11px; color:var(--ink2); overflow-wrap:anywhere;">{{ $order->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700;">฿{{ number_format($order->total_amount, 2) }}</td>
                                <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                    <span class="tp-pill" style="color:{{ $statusColor }}; background:color-mix(in srgb, {{ $statusColor }} 16%, transparent);">{{ $statusLabel }}</span>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                    <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── สินค้าขายดี Top 5 ─────────────────────────────────── --}}
    @if($topProducts->isNotEmpty())
    <div class="tp-card" style="padding:20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
            <div class="tp-section-h">🏆 สินค้าขายดี Top 5</div>
            <a href="{{ route('seller.products.index') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
            @foreach($topProducts as $index => $product)
                <div style="padding:13px; border-radius:15px; box-shadow:var(--inset-sm); min-width:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px;">
                        <span class="tp-pill tp-pill-gold" style="font-weight:800;">#{{ $index + 1 }}</span>
                        <span style="font-size:11px; color:var(--ink2); white-space:nowrap;">{{ number_format($product->sales_count ?? 0) }} ขาย</span>
                    </div>
                    @if($product->images->first())
                        <img src="{{ $product->images->first()->image_url }}" alt="รูปสินค้า {{ $product->name }}" loading="lazy"
                             style="width:100%; height:118px; object-fit:cover; border-radius:12px; margin-bottom:10px;">
                    @else
                        <div style="width:100%; height:118px; border-radius:12px; margin-bottom:10px; display:grid; place-items:center; font-size:34px; box-shadow:var(--inset-sm);">📦</div>
                    @endif
                    <div style="font-size:12.5px; font-weight:700; line-height:1.4; overflow-wrap:anywhere;">{{ Str::limit($product->name, 46) }}</div>
                    <div class="tp-num" style="font-size:16px; font-weight:800; color:var(--deep1); margin-top:5px;">฿{{ number_format($product->price, 0) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
/**
 * คำขวัญสลับอัตโนมัติ
 *
 * ⚠️ ใช้ Js::from ส่งข้อมูลเข้า JS ตามกฎโปรเจกต์
 *    ห้ามใช้ไดเรกทีฟ json ที่มีวงเล็บซ้อน เพราะ Blade compiler พัง
 *    (และห้ามพิมพ์ชื่อไดเรกทีฟพร้อมเครื่องหมาย @ ไว้ในคอมเมนต์ด้วย
 *     Blade มองไม่ออกว่าเป็นคอมเมนต์ JS แล้วจะคอมไพล์มันจริงๆ)
 */
function sloganCarousel() {
    return {
        slogans: {{ Js::from(collect($slogans ?? [])->values()) }},
        index: 0,
        autoPlay: true,
        _timer: null,

        get currentSlogan() {
            return this.slogans[this.index] || { content: '', icon: '🇹🇭', category_name: '', author: '' };
        },

        init() {
            if (this.slogans.length > 1) this.start();
        },

        start() {
            this.stop();
            // หยุดเองเมื่อผู้ใช้สลับไปแท็บอื่น จะได้ไม่หมุนทิ้งเปล่าๆ
            this._timer = setInterval(() => {
                if (document.hidden || !this.autoPlay) return;
                this.index = (this.index + 1) % this.slogans.length;
            }, 6000);
        },

        stop() { if (this._timer) { clearInterval(this._timer); this._timer = null; } },

        goTo(i) { this.index = i; },

        toggleAuto() { this.autoPlay = !this.autoPlay; },
    };
}
</script>
@endpush
