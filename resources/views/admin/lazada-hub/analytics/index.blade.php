@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'อนาไลติก Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — อนาไลติกสายเงิน affiliate (ธีม V4 "นวลทองคำ")
     กรวยการแปลง · KPI · สาเหตุที่ยังไม่อนุมัติ · สุขภาพการ sync · ตารางท็อป

     🚨 กฎบนหน้าจอนี้: เงิน "ยืนยันแล้ว" กับ "รอยืนยัน" ต้องแยกกันเสมอ
        ห้ามเอามารวมเป็นตัวเลขเดียว เพราะ "ค่าคอมตามรายงาน" ≠ "เงินที่ได้รับจริง"
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · อนาไลติก
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-chart-line" style="color:var(--accent1);"></i> อนาไลติกสายเงิน Affiliate
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">
                คลิก → ออเดอร์ → ลาซาด้าเคลียร์เงิน → จ่ายให้สมาชิก · ดูได้ว่าเงินติดอยู่ตรงไหน
            </p>
        </div>
        <a href="{{ route('admin.lazada-hub.analytics.clicks') }}" class="tp-btn">
            <i class="fas fa-hand-pointer"></i> <span>บันทึกการคลิก</span>
        </a>
    </div>

    {{-- ── ไม่มีแพลตฟอร์ม lazada ในระบบ ── --}}
    @if(! $platform)
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="font-weight:700;color:var(--ink);margin-bottom:4px;">
                <i class="fas fa-triangle-exclamation" style="color:#d9534f;"></i> ยังไม่มีแพลตฟอร์ม "lazada" ในฐานข้อมูล
            </div>
            <p class="tp-muted" style="margin:0;font-size:.85rem;line-height:1.7;">
                ตัวเลขทั้งหมดด้านล่างจึงเป็นศูนย์ — ต้องมีแถวแพลตฟอร์ม slug = <b>lazada</b> ก่อน ระบบถึงจะผูกลิงก์/ออเดอร์เข้าด้วยกันได้
            </p>
        </div>
    @endif

    {{-- ── ตัวกรองช่วงวันที่ ── --}}
    @php
        // ปุ่มลัดช่วงเวลา — คำนวณวันที่ล่วงหน้าใน @php กัน logic ซ้อนใน directive
        $today = \Carbon\Carbon::today();
        $presets = [
            ['label' => '7 วัน', 'days' => 7],
            ['label' => '30 วัน', 'days' => 30],
            ['label' => '90 วัน', 'days' => 90],
            ['label' => '1 ปี', 'days' => 365],
        ];
    @endphp
    <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--ink);">
            <i class="fas fa-calendar-days" style="color:var(--accent1);"></i>
            <span>ช่วงข้อมูล</span>
            <b class="tp-num">{{ $filters['from'] }}</b>
            <span class="tp-muted">ถึง</span>
            <b class="tp-num">{{ $filters['to'] }}</b>
            <span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:10px;">{{ number_format($filters['days']) }} วัน</span>
        </div>

        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @foreach($presets as $preset)
                @php
                    $presetFrom = $today->copy()->subDays($preset['days'] - 1)->toDateString();
                    $isActive = ($filters['from'] === $presetFrom && $filters['to'] === $today->toDateString());
                @endphp
                <a href="{{ route('admin.lazada-hub.analytics.index', ['from' => $presetFrom, 'to' => $today->toDateString()]) }}"
                   class="tp-btn tp-btn-sm"
                   style="{{ $isActive ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : '' }}">
                    {{ $preset['label'] }}
                </a>
            @endforeach
        </div>

        <form method="GET" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="tp-input tp-num" style="width:auto;padding:8px 12px;font-size:.82rem;">
            <span class="tp-muted" style="font-size:.8rem;">ถึง</span>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="tp-input tp-num" style="width:auto;padding:8px 12px;font-size:.82rem;">
            <button class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-filter"></i> <span>กรอง</span></button>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════
         สุขภาพการ sync + ธงยืนยันชื่อฟิลด์ (สำคัญที่สุด)
         ══════════════════════════════════════════════════ --}}
    @php
        $mv = $syncHealth['mapping_verified'];
        $mvColor = $mv ? '#5aa07e' : '#e0a52e';
    @endphp
    <div class="tp-card" style="border-left:5px solid {{ $mvColor }};display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <i class="fas {{ $mv ? 'fa-shield-halved' : 'fa-triangle-exclamation' }}" style="color:{{ $mvColor }};font-size:1.6rem;margin-top:2px;"></i>
            <div style="flex:1;min-width:240px;">
                @if($mv)
                    <div style="font-weight:800;color:var(--ink);font-size:1.05rem;">ยืนยันชื่อฟิลด์แล้ว — ระบบอนุมัติอัตโนมัติได้เมื่อผ่านด่านครบ</div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:.85rem;line-height:1.7;">
                        ออเดอร์ที่ลาซาด้าเคลียร์เงินให้เราแล้ว และผ่านด่านตรวจครบทุกข้อ จะถูกตั้งเป็น "อนุมัติ" ให้อัตโนมัติ
                        ส่วนการจ่ายเงินเข้าวอลเลตสมาชิกยังต้องมีคนกดเองอยู่
                    </p>
                @else
                    <div style="font-weight:800;color:var(--ink);font-size:1.05rem;">โหมดปลอดภัย: ยังไม่ยืนยันชื่อฟิลด์จากลาซาด้า — ระบบจะไม่อนุมัติจ่ายอัตโนมัติ</div>
                    <p class="tp-muted" style="margin:6px 0 0;font-size:.85rem;line-height:1.7;">
                        ตอนนี้เรายัง "อ่านรายงานลาซาด้าไม่เป็น" (ชื่อฟิลด์ทุกตัวยังเป็นการเดา) ทุกออเดอร์จึงถูกค้างไว้ที่ <b>รออนุมัติ</b> เสมอ
                        เป็นค่าเริ่มต้นที่ตั้งใจให้เป็นแบบนี้ — จ่ายค่าคอมออกไปก่อนที่ลาซาด้าจะจ่ายให้เรา = บริษัทเสียเงินจริง
                    </p>
                @endif
                <div class="tp-muted" style="margin-top:8px;font-size:.74rem;">
                    ธง: <code style="color:var(--ink);">{{ $syncHealth['mapping_key'] }}</code>
                    = <b style="color:{{ $mvColor }};">{{ $mv ? 'เปิด' : 'ปิด' }}</b>
                    <span style="opacity:.7;">(ตั้งค่าที่ตาราง mlm_global_settings)</span>
                </div>
            </div>
        </div>

        <hr class="tp-divider">

        {{-- ตัวเลขการ sync --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
            <div>
                <div class="tp-muted" style="font-size:.72rem;">sync ล่าสุดเมื่อ</div>
                <div class="tp-num" style="font-weight:700;color:var(--ink);font-size:.95rem;">
                    {{ $syncHealth['last_synced_at'] ? $syncHealth['last_synced_at']->format('d/m/Y H:i') : 'ยังไม่เคย sync' }}
                </div>
            </div>
            <div>
                <div class="tp-muted" style="font-size:.72rem;">ออเดอร์ที่ sync มาแล้ว</div>
                <div class="tp-num" style="font-weight:700;color:var(--ink);font-size:.95rem;">
                    {{ number_format($syncHealth['synced_total']) }} / {{ number_format($syncHealth['orders_total']) }}
                </div>
            </div>
            <div>
                <div class="tp-muted" style="font-size:.72rem;">สถานะที่ถือว่า "เคลียร์เงินแล้ว"</div>
                <div style="font-weight:700;color:var(--ink);font-size:.8rem;">
                    {{ implode(' · ', $syncHealth['settled_statuses']) }}
                </div>
            </div>
        </div>

        {{-- ชื่อฟิลด์จริงที่ลาซาด้าส่งมา (กุญแจปลดล็อกการล็อก mapping) --}}
        <div class="tp-inset" style="padding:12px 14px;border-radius:12px;">
            <div style="font-weight:700;color:var(--ink);font-size:.85rem;margin-bottom:8px;">
                <i class="fas fa-key" style="color:var(--accent2);"></i> ชื่อฟิลด์จริงที่ลาซาด้าส่งมา
            </div>
            @if(empty($syncHealth['discovered_keys']))
                <p class="tp-muted" style="margin:0;font-size:.82rem;line-height:1.7;">
                    ยังไม่เคยเห็นแถวจริงสักแถว (รายงาน conversion คืนข้อมูล 0 แถวมาตลอด)
                    <br>
                    เมื่อมีแถวแรกเข้ามา ระบบจะเก็บแถวดิบไว้ใน <code>order_data.lazada_raw</code> แล้วชื่อฟิลด์จะขึ้นมาแสดงตรงนี้เอง
                    — เอาไปล็อก mapping แล้วค่อยเปิดธงยืนยัน
                </p>
            @else
                <p class="tp-muted" style="margin:0 0 8px;font-size:.78rem;">
                    จากออเดอร์ล่าสุด <b class="tp-num" style="color:var(--ink);">{{ $syncHealth['discovered_from'] }}</b>
                    · พบ {{ number_format(count($syncHealth['discovered_keys'])) }} ฟิลด์
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($syncHealth['discovered_keys'] as $key)
                        <span class="tp-pill tp-num" style="background:var(--surf);color:var(--ink);font-size:10.5px;">{{ $key }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         กรวยการแปลง (หัวใจของหน้านี้)
         ══════════════════════════════════════════════════ --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div class="tp-section-h"><i class="fas fa-filter-circle-dollar" style="color:var(--accent1);"></i> กรวยการแปลง</div>
            <span class="tp-muted" style="font-size:.72rem;">ขั้น 1–2 นับเป็น "คลิก" · ขั้น 3–4 นับเป็น "ออเดอร์"</span>
        </div>

        @if($funnel[0]['count'] === 0)
            <div style="text-align:center;padding:28px 16px;color:var(--ink2);">
                <i class="fas fa-hand-pointer" style="font-size:2rem;opacity:.35;"></i>
                <p style="margin:12px 0 0;font-size:.9rem;">ยังไม่มีคลิกในช่วงวันที่ที่เลือก</p>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.8rem;">
                    คลิกจะถูกบันทึกเมื่อลูกค้ากดลิงก์ <code>/go/{short_code}</code> ของสมาชิก — ลองขยายช่วงวันที่ดู
                </p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($funnel as $i => $step)
                    <div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:5px;">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <span class="tp-pill tp-num" style="background:{{ $step['color'] }};color:#fff;font-size:10px;">{{ $i + 1 }}</span>
                                <i class="fas {{ $step['icon'] }}" style="color:{{ $step['color'] }};font-size:.85rem;"></i>
                                <span style="font-weight:700;color:var(--ink);font-size:.9rem;">{{ $step['label'] }}</span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:10px;">
                                <span class="tp-num" style="font-weight:800;color:var(--ink);font-size:1.05rem;">{{ number_format($step['count']) }}</span>
                                <span class="tp-muted" style="font-size:.74rem;">{{ $step['unit'] }}</span>
                                @if($step['pct_prev'] !== null)
                                    <span class="tp-pill tp-num" style="background:var(--surf);color:var(--ink2);font-size:10px;">
                                        {{ number_format($step['pct_prev'], 2) }}% จากขั้นก่อน
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="tp-inset" style="height:14px;border-radius:9px;overflow:hidden;background:var(--surf);">
                            <div style="height:100%;width:{{ $step['width'] }}%;background:{{ $step['color'] }};border-radius:9px;transition:width .4s ease;"></div>
                        </div>
                        <div class="tp-muted" style="font-size:.72rem;margin-top:4px;">{{ $step['hint'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 🚨 ตัวเลขที่ต้องเห็น: ลาซาด้าบอกเคลียร์แล้ว แต่ด่านอื่นยังไม่ผ่าน --}}
        @if($orderStates['settled_but_blocked'] > 0)
            <div class="tp-inset" style="padding:11px 14px;border-radius:12px;border-left:3px solid #e0a52e;">
                <span style="color:var(--ink);font-size:.85rem;">
                    <i class="fas fa-circle-exclamation" style="color:#e0a52e;"></i>
                    ลาซาด้าบอกว่าเคลียร์เงินแล้ว แต่ยังไม่อนุมัติเพราะติดด่านอื่น:
                    <b class="tp-num">{{ number_format($orderStates['settled_but_blocked']) }}</b> ออเดอร์
                </span>
                <div class="tp-muted" style="font-size:.74rem;margin-top:4px;">ดูสาเหตุรายข้อได้ที่หัวข้อ "ทำไมยังไม่อนุมัติ" ด้านล่าง</div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         KPI
         ══════════════════════════════════════════════════ --}}
    <div>
        <div class="tp-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
            ตัวชี้วัดหลัก · ตัวเลขเงินระบุกำกับเสมอว่า "ยืนยันแล้ว" หรือ "รอยืนยัน"
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
            @foreach($kpis as $kpi)
                @php
                    $badgeBg = $kpi['badge'] === 'ยืนยันแล้ว' ? '#5aa07e' : '#e0a52e';
                @endphp
                <div class="tp-card" style="display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                        <span class="tp-muted" style="font-size:.74rem;">{{ $kpi['label'] }}</span>
                        <i class="fas {{ $kpi['icon'] }}" style="color:{{ $kpi['color'] }};"></i>
                    </div>
                    <div class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);word-break:break-all;">{{ $kpi['value'] }}</div>
                    @if($kpi['badge'])
                        <span class="tp-pill" style="background:{{ $badgeBg }};color:#fff;font-size:9.5px;align-self:flex-start;">{{ $kpi['badge'] }}</span>
                    @endif
                    <div class="tp-muted" style="font-size:.7rem;line-height:1.5;">{{ $kpi['hint'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ทำไมยังไม่อนุมัติ
         ══════════════════════════════════════════════════ --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div class="tp-section-h"><i class="fas fa-circle-question" style="color:#e0a52e;"></i> ทำไมยังไม่อนุมัติ</div>
            <span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:10.5px;">
                ออเดอร์รออนุมัติ <b class="tp-num" style="color:var(--ink);">{{ number_format($gateBreakdown['pending_total']) }}</b>
            </span>
        </div>

        @if($gateBreakdown['is_fallback'])
            <div class="tp-muted" style="font-size:.74rem;">
                <i class="fas fa-circle-info"></i>
                ฐานข้อมูลนี้อ่านฟิลด์ JSON ด้วยคำสั่ง SQL ไม่ได้ — ตัวเลขด้านล่างนับจากออเดอร์ล่าสุดไม่เกิน
                {{ number_format($gateBreakdown['scan_limit']) }} รายการ
            </div>
        @endif

        @if($gateBreakdown['pending_total'] === 0)
            <div style="text-align:center;padding:24px 16px;color:var(--ink2);">
                <i class="fas fa-circle-check" style="font-size:1.8rem;opacity:.35;"></i>
                <p style="margin:10px 0 0;font-size:.9rem;">ไม่มีออเดอร์ค้างรออนุมัติในช่วงนี้</p>
            </div>
        @elseif(empty($gateBreakdown['reasons']))
            <div class="tp-muted" style="font-size:.85rem;line-height:1.7;">
                มีออเดอร์ค้างอยู่ แต่อ่านสาเหตุจากด่านตรวจไม่ได้ — ตรวจข้อมูลดิบที่
                <a href="{{ route('admin.marketplace.orders.index') }}" style="color:var(--accent1);">หน้าออเดอร์</a> โดยตรง
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:9px;">
                @foreach($gateBreakdown['reasons'] as $reason)
                    <div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                            <span style="color:var(--ink);font-size:.86rem;">
                                <i class="fas fa-xmark" style="color:#d9534f;"></i> {{ $reason['label'] }}
                            </span>
                            <span style="white-space:nowrap;">
                                <b class="tp-num" style="color:var(--ink);">{{ number_format($reason['count']) }}</b>
                                <span class="tp-muted" style="font-size:.76rem;">ออเดอร์ ({{ number_format($reason['pct'], 1) }}%)</span>
                            </span>
                        </div>
                        <div class="tp-inset" style="height:8px;border-radius:6px;overflow:hidden;background:var(--surf);margin-top:4px;">
                            <div style="height:100%;width:{{ min(100, $reason['pct']) }}%;background:#e0a52e;border-radius:6px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="tp-muted" style="margin:0;font-size:.74rem;line-height:1.7;">
                * ออเดอร์หนึ่งใบตกได้หลายด่านพร้อมกัน ผลรวมจึงมากกว่าจำนวนออเดอร์ค้างได้
                · เงินที่ยังไม่ยืนยันรวม <b class="tp-num" style="color:#e0a52e;">฿{{ number_format($orderStates['commission_unconfirmed'], 2) }}</b>
            </p>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ตารางท็อป
         ══════════════════════════════════════════════════ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;">

        {{-- สินค้าที่ทำเงินได้ดีที่สุด --}}
        <div class="tp-card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <div class="tp-section-h"><i class="fas fa-trophy" style="color:var(--accent2);"></i> สินค้าที่ทำเงินได้ดีที่สุด</div>
                <span class="tp-pill" style="background:#5aa07e;color:#fff;font-size:9.5px;">ยืนยันแล้ว</span>
            </div>
            @if($topProducts->isEmpty())
                <div style="text-align:center;padding:30px 16px;color:var(--ink2);">
                    <i class="fas fa-box-open" style="font-size:1.6rem;opacity:.35;"></i>
                    <p style="margin:10px 0 0;font-size:.86rem;">ยังไม่มีสินค้าที่ค่าคอมถูกยืนยันในช่วงนี้</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:420px;">
                        <thead>
                            <tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                                <th style="padding:10px 12px;font-weight:600;">สินค้า</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ออเดอร์</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ยอดขาย</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ค่าคอม</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $row)
                                <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);">
                                    <td style="padding:9px 12px;color:var(--ink);max-width:230px;">
                                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $row->product_name ?: 'สินค้า #'.$row->product_id.' (ไม่พบในแคตตาล็อก)' }}
                                        </div>
                                    </td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;color:var(--ink2);">{{ number_format((int) $row->orders_count) }}</td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;color:var(--ink2);">฿{{ number_format((float) $row->sales_sum, 2) }}</td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;font-weight:700;color:#5aa07e;">฿{{ number_format((float) $row->commission_sum, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- สมาชิกที่แนะนำได้ดีที่สุด --}}
        <div class="tp-card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <div class="tp-section-h"><i class="fas fa-user-group" style="color:var(--accent1);"></i> สมาชิกที่แนะนำได้ดีที่สุด</div>
                <span class="tp-pill" style="background:#5aa07e;color:#fff;font-size:9.5px;">ยืนยันแล้ว</span>
            </div>
            @if($topAffiliates->isEmpty())
                <div style="text-align:center;padding:30px 16px;color:var(--ink2);">
                    <i class="fas fa-user-slash" style="font-size:1.6rem;opacity:.35;"></i>
                    <p style="margin:10px 0 0;font-size:.86rem;">ยังไม่มีสมาชิกที่ค่าคอมถูกยืนยันในช่วงนี้</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:420px;">
                        <thead>
                            <tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                                <th style="padding:10px 12px;font-weight:600;">สมาชิก</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ออเดอร์</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ยอดขาย</th>
                                <th style="padding:10px 12px;font-weight:600;text-align:right;">ค่าคอม</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAffiliates as $row)
                                <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);">
                                    <td style="padding:9px 12px;color:var(--ink);max-width:230px;">
                                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $row->user_name ?: 'สมาชิก #'.$row->user_id }}
                                            <span class="tp-muted tp-num" style="font-size:.7rem;">#{{ $row->user_id }}</span>
                                        </div>
                                    </td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;color:var(--ink2);">{{ number_format((int) $row->orders_count) }}</td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;color:var(--ink2);">฿{{ number_format((float) $row->sales_sum, 2) }}</td>
                                    <td class="tp-num" style="padding:9px 12px;text-align:right;font-weight:700;color:#5aa07e;">฿{{ number_format((float) $row->commission_sum, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── สรุปสถานะออเดอร์ + ทางลัด ── --}}
    <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
        <span style="font-size:.84rem;color:var(--ink2);">ออเดอร์ในช่วงนี้ <b class="tp-num" style="color:var(--ink);">{{ number_format($orderStates['total']) }}</b></span>
        <span style="font-size:.84rem;color:var(--ink2);">ยืนยันแล้ว <b class="tp-num" style="color:#5aa07e;">{{ number_format($orderStates['settled']) }}</b></span>
        <span style="font-size:.84rem;color:var(--ink2);">รออนุมัติ <b class="tp-num" style="color:#e0a52e;">{{ number_format($orderStates['pending']) }}</b></span>
        <span style="font-size:.84rem;color:var(--ink2);">ยกเลิก <b class="tp-num" style="color:#d9534f;">{{ number_format($orderStates['cancelled']) }}</b></span>
        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('admin.marketplace.orders.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-receipt"></i> <span>ออเดอร์ทั้งหมด</span></a>
            <a href="{{ route('admin.marketplace.commissions.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-coins"></i> <span>ค่าคอมมิชชั่น</span></a>
        </div>
    </div>
</div>
@endsection
