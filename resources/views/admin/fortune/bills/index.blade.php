@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 🧾 ศูนย์รวมบิลดูดวง — ยุบ billing + readings + celtic-cross list มาไว้ที่เดียว
     ทุกปุ่มยิงไปที่ route เดิมทั้งหมด ไม่มี endpoint ใหม่ --}}
@php
    $tpQ = fn (array $over = []) => request()->fullUrlWithQuery(array_merge($over, ['page' => null]));
@endphp
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · บิล</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ศูนย์รวมบิลดูดวง 🧾</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                บิลทุกแพคเกจ ทุกช่องทาง ที่เดียว — Deep 39฿ · Celtic 99฿ · ไพ่ฟรี · พื้นฐาน
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.celtic-cross.index'))
                <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gear"></i> ตั้งค่า Celtic
                </a>
            @endif
            @if(Route::has('admin.fortune.celtic-cross.emergency-recover'))
                <a href="{{ route('admin.fortune.celtic-cross.emergency-recover') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-kit-medical"></i> กู้บิลด่วน
                </a>
            @endif
            @if(Route::has('admin.fortune.billing.floating-bills'))
                <a href="{{ route('admin.fortune.billing.floating-bills') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-clipboard-list" style="color:#d6824a;"></i> บิลลอย
                    @if(($stats['floating'] ?? 0) > 0)
                        <span class="tp-pill" style="background:#d6824a; color:#fff; margin-left:5px; font-size:10px;">{{ $stats['floating'] }}</span>
                    @endif
                </a>
            @endif
            @if(Route::has('admin.fortune.billing.export-revenue'))
                <a href="{{ route('admin.fortune.billing.export-revenue', ['date_from' => $filters['date_from'] ?? null, 'date_to' => $filters['date_to'] ?? null]) }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-chart-line"></i> Export รายได้
                </a>
            @endif
            @if(Route::has('admin.fortune.bills.export'))
                <a href="{{ route('admin.fortune.bills.export', request()->query()) }}" class="tp-btn tp-btn-sm tp-btn-primary">
                    <i class="fas fa-file-arrow-down"></i> Export CSV
                </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI (กดได้ = กรองสถานะนั้น) ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        @php
            $tpKpis = [
                ['ทั้งหมดในขอบเขต', number_format($stats['total']), 'fa-layer-group', null, '', ''],
                // ⏱️ โชว์เฉพาะ "ยังลุ้นได้เงิน" เป็นตัวเลขหลัก — ของค้างเก่าแยกไปอีกการ์ด
                //    ไม่งั้นตัวเลขหลอกตา (prod: บิลรอชำระ 417 ใบ เก่ากว่า 30 วันทั้งหมด = ไม่มีอะไรให้ตาม)
                ['รอชำระ (ใหม่ ≤7 วัน)', number_format($stats['pending_fresh']), 'fa-hourglass-half', '#a9791a', '', 'pending_fresh'],
                ['🪦 ค้างเก่า (เกิน 7 วัน)', number_format($stats['pending'] - $stats['pending_fresh']), 'fa-ghost', '#8c8c96', '', 'pending_stale'],
                ['จ่ายแล้ว', number_format($stats['paid']), 'fa-circle-check', '#5aa07e', '', 'paid'],
                ['รายได้จริง', number_format($stats['revenue'], 0), 'fa-coins', '#e0a52e', '฿', 'paid'],
                ['บิลลอย', number_format($stats['floating']), 'fa-clipboard-list', '#d6824a', '', 'floating'],
            ];
        @endphp
        @foreach ($tpKpis as [$label, $value, $icon, $iconBg, $prefix, $statusKey])
            <a href="{{ $statusKey ? $tpQ(['status' => $statusKey]) : $tpQ(['status' => null]) }}"
               class="tp-card" style="padding:16px; text-decoration:none; {{ ($filters['status'] ?? '') === $statusKey && $statusKey ? 'outline:2px solid var(--accent1);' : '' }}">
                <div style="display:flex; align-items:center; gap:11px;">
                    <div class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; display:flex; align-items:center; justify-content:center;@if($iconBg) background:{{ $iconBg }};@endif">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:21px; font-weight:800; line-height:1;">{{ $prefix }}{{ $value }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:20px;">

        {{-- แพคเกจ --}}
        <div style="margin-bottom:14px;">
            <div style="font-size:12px; color:var(--ink2); font-weight:700; margin-bottom:7px;">📦 แพคเกจ</div>
            <div style="display:flex; flex-wrap:wrap; gap:7px;">
                <a href="{{ $tpQ(['package' => null]) }}" class="tp-pill"
                   style="text-decoration:none; {{ ($filters['package'] ?? '') === '' ? 'background:var(--accent1); color:#fff;' : '' }}">ทั้งหมด</a>
                @foreach($packages as $pkgKey => $pkg)
                    <a href="{{ $tpQ(['package' => $pkgKey]) }}" class="tp-pill"
                       style="text-decoration:none; {{ ($filters['package'] ?? '') === $pkgKey ? 'background:var(--accent1); color:#fff;' : '' }}">{{ $pkg[0] }}</a>
                @endforeach
            </div>
        </div>

        {{-- แพลตฟอร์ม --}}
        <div style="margin-bottom:14px;">
            <div style="font-size:12px; color:var(--ink2); font-weight:700; margin-bottom:7px;">📱 ช่องทางที่ลูกค้าเข้ามา</div>
            <div style="display:flex; flex-wrap:wrap; gap:7px;">
                <a href="{{ $tpQ(['platform' => null]) }}" class="tp-pill"
                   style="text-decoration:none; {{ ($filters['platform'] ?? '') === '' ? 'background:var(--accent1); color:#fff;' : '' }}">ทั้งหมด</a>
                @foreach($platforms as $pfKey => $pf)
                    <a href="{{ $tpQ(['platform' => $pfKey]) }}" class="tp-pill"
                       style="text-decoration:none; {{ ($filters['platform'] ?? '') === $pfKey ? 'background:var(--accent1); color:#fff;' : 'color:'.$pf[1].';' }}">
                        <i class="{{ $pf[2] }}"></i> {{ $pf[0] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 🏬 สาขา/เพจ (2026-08-10) — โชว์เฉพาะตอนมีมากกว่า 1 สาขา จะได้ไม่รกตอนใช้เพจเดียว --}}
        @if(($fortunePages ?? collect())->count() > 1)
            <div style="margin-bottom:14px;">
                <div style="font-size:12px; color:var(--ink2); font-weight:700; margin-bottom:7px;">🏬 สาขา / เพจต้นทาง</div>
                <div style="display:flex; flex-wrap:wrap; gap:7px;">
                    <a href="{{ $tpQ(['fortune_page' => null]) }}" class="tp-pill"
                       style="text-decoration:none; {{ ($filters['fortune_page'] ?? '') === '' ? 'background:var(--accent1); color:#fff;' : '' }}">ทุกสาขา</a>
                    @foreach($fortunePages as $fp)
                        <a href="{{ $tpQ(['fortune_page' => $fp->id]) }}" class="tp-pill"
                           style="text-decoration:none; {{ (string) ($filters['fortune_page'] ?? '') === (string) $fp->id ? 'background:var(--accent1); color:#fff;' : '' }}">
                            {{ $fp->display_label }}{{ $fp->is_active ? '' : ' (ปิด)' }}
                        </a>
                    @endforeach
                    {{-- แถวเก่าที่ backfill ไม่ถึง / งานที่รันจากคอนโซล — ต้องมองเห็นได้ ไม่ใช่หายเงียบ --}}
                    <a href="{{ $tpQ(['fortune_page' => 'none']) }}" class="tp-pill"
                       style="text-decoration:none; {{ ($filters['fortune_page'] ?? '') === 'none' ? 'background:var(--accent1); color:#fff;' : 'color:var(--ink2);' }}">ไม่ระบุสาขา</a>
                </div>
            </div>
        @endif

        {{-- ค้นหา / สถานะ / วันที่ --}}
        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; align-items:end;">
            <input type="hidden" name="package" value="{{ $filters['package'] ?? '' }}">
            <input type="hidden" name="platform" value="{{ $filters['platform'] ?? '' }}">
            {{-- 🏬 ไม่งั้นกดค้นหาแล้วตัวกรองสาขาหลุด (เหมือน package/platform) --}}
            <input type="hidden" name="fortune_page" value="{{ $filters['fortune_page'] ?? '' }}">

            <div style="grid-column:span 2; min-width:0;">
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">🔍 ค้นหา (ชื่อ / เลขบิล / PSID / LINE id / #id)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="เช่น FTU-260806-G4674 หรือ ชื่อลูกค้า"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        @php
                            $tpStatuses = [
                                '' => '— ทุกสถานะ —',
                                'paid' => '✅ จ่ายแล้ว',
                                'pending_fresh' => '⏳ รอชำระ ใหม่ ≤7 วัน (ยังลุ้นได้เงิน)',
                                'pending_stale' => '🪦 ค้างเก่า เกิน 7 วัน (ซากบิล)',
                                'pending' => '⏳ รอชำระ (ทั้งหมด)',
                                'unpaid' => 'ยังไม่จ่าย (ทั้งหมด)',
                                'cancelled' => '❌ ยกเลิก',
                                'abandoned' => '🕳️ ปิดเงียบ (ออกบิลแล้วไม่จ่าย)',
                                'no_bill' => '💬 คุยแล้วหายไป (ไม่เคยออกบิล)',
                                'floating' => '💸 บิลลอย (เงินเข้าไม่รู้เจ้าของ)',
                                'stuck_celtic' => '🧊 Celtic ค้าง (จ่ายแล้ว ไพ่ไม่ครบ)',
                                'stuck_deep' => '🧊 Deep ค้าง (จ่ายแล้ว ไม่มีคำทำนาย)',
                                'free' => '🎁 ฟรี',
                            ];
                        @endphp
                        @foreach($tpStatuses as $sKey => $sLabel)
                            <option value="{{ $sKey }}" {{ ($filters['status'] ?? '') === $sKey ? 'selected' : '' }}>{{ $sLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ตั้งแต่วันที่</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ถึงวันที่</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">🤖 AI provider</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="ai_provider" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">— ทั้งหมด —</option>
                        @foreach($aiProviders as $ap)
                            <option value="{{ $ap }}" {{ ($filters['ai_provider'] ?? '') === $ap ? 'selected' : '' }}>{{ $ap }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.fortune.bills.index') }}" class="tp-btn"><i class="fas fa-eraser"></i> ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-receipt"></i> รายการบิล
            <span class="tp-pill tp-pill-soft" style="margin-left:8px;">{{ number_format($bills->total()) }} รายการ</span>
        </div>

        @if($bills->isEmpty())
            <div style="text-align:center; padding:44px 16px; color:var(--ink2);">
                <div style="font-size:34px; margin-bottom:8px;">🗂️</div>
                <div style="font-size:14px;">ไม่พบบิลตามเงื่อนไขที่กรอง</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2);">
                            @foreach(['Bill','สาขา','ช่องทาง','ลูกค้า','แพคเกจ','สถานะ','ค่าครู','เวลา','จัดการ'] as $th)
                                <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; {{ $th === 'จัดการ' ? 'text-align:right;' : '' }}">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bills as $bill)
                            @php
                                $isCeltic = $bill->reading_type === \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS;
                                $isDeep = $bill->reading_type === \App\Models\FortuneReading::READING_TYPE_DEEP;
                                $isPaid = (bool) $bill->is_paid;
                                $cStatus = (string) $bill->conversation_status;

                                $pfKey = in_array($bill->platform, ['facebook', 'line'], true) ? $bill->platform : 'other';
                                $pf = $platforms[$pfKey];

                                if ($bill->is_floating) {
                                    $pill = ['💸 บิลลอย', 'background:rgba(214,130,74,.18); color:#a85f2c;'];
                                } elseif ($bill->isCancelled()) {
                                    $pill = ['❌ '.$bill->getCancellationReasonLabelOrNull(), 'background:rgba(217,83,79,.16); color:#d9534f;'];
                                } elseif ($cStatus === \App\Models\FortuneReading::STATUS_COMPLETED) {
                                    // 🩹 (2026-08-07) แยก "ออกบิลแล้วไม่จ่าย" ออกจาก "คุยแล้วหายไปก่อนออกบิล"
                                    //   เดิมเหมารวมเป็น "ปิดเงียบ" หมด → prod โชว์ 8,258 ใบ
                                    //   ทั้งที่เคยออกบิลจริงแค่ 309 ใบ = ดูเหมือนเสียบิลเกินจริง ~26 เท่า
                                    if ($isPaid) {
                                        $pill = ['✅ จบแล้ว', 'background:rgba(90,160,126,.18); color:#3f7a5c;'];
                                    } elseif ((float) $bill->amount_paid > 0) {
                                        $pill = ['🕳️ ปิดเงียบ (ออกบิลแล้วไม่จ่าย)', 'background:rgba(140,140,150,.18); color:#70707a;'];
                                    } else {
                                        $pill = ['💬 คุยแล้วหายไป (ไม่เคยออกบิล)', 'background:rgba(140,140,150,.12); color:#8c8c96;'];
                                    }
                                } elseif (in_array($cStatus, \App\Models\FortuneReading::PENDING_DISPLAY_STATUSES, true)) {
                                    $pill = ['⏳ รอชำระ', 'background:rgba(224,165,46,.18); color:#a9791a;'];
                                } else {
                                    $pill = $isPaid
                                        ? ['🔮 กำลังใช้บริการ', 'background:rgba(86,137,184,.18); color:#3f6a94;']
                                        : ['💬 กำลังคุย', 'background:rgba(224,165,46,.18); color:#a9791a;'];
                                }
                            @endphp
                            <tr x-data="{ submitting: false }" style="border-top:1px solid var(--sd);">
                                {{-- Bill --}}
                                <td style="padding:11px 12px; font-family:monospace; font-size:12px; color:var(--ink);">
                                    <span style="cursor:pointer;" title="คลิกเพื่อคัดลอกเลขบิล"
                                          onclick="tpCopy(this, '{{ $bill->bill_reference ?? '#'.$bill->id }}')">
                                        {{ $bill->bill_reference ?? '#'.$bill->id }}
                                    </span>
                                    @if(data_get($bill->conversation_state, 'black_magic_mode'))
                                        <span class="tp-pill" style="display:inline-block; margin-top:4px; background:rgba(123,80,168,.16); color:#7b50a8; font-size:10px; font-weight:700;">🪬 คุณไสย</span>
                                    @endif
                                    @if($bill->slipok_verified_at)
                                        {{-- ✅ SlipOK ตัดให้เอง — ต่างจากบิลที่แอดมิน/SMS จับคู่ให้ (ส่วนใหญ่เป็นแบบหลัง) --}}
                                        <span class="tp-pill" title="SlipOK ตรวจสลิปผ่านเมื่อ {{ $bill->slipok_verified_at->format('d/m/y H:i') }}"
                                              style="display:inline-block; margin-top:4px; background:rgba(90,160,126,.16); color:#3f7a5c; font-size:10px; font-weight:700;">🧾 SlipOK</span>
                                    @endif
                                </td>

                                {{-- 🏬 สาขา/เพจต้นทาง (2026-08-10) --}}
                                <td style="padding:11px 12px; white-space:nowrap;">
                                    @if($bill->fortunePage)
                                        <span class="tp-pill tp-pill-soft" style="font-weight:700;">{{ $bill->fortunePage->display_label }}</span>
                                    @else
                                        <span style="color:var(--ink2); font-size:12px;">ไม่ระบุ</span>
                                    @endif
                                </td>

                                {{-- ช่องทาง --}}
                                <td style="padding:11px 12px; white-space:nowrap;">
                                    <span class="tp-pill" style="background:{{ $pf[1] }}1f; color:{{ $pf[1] }}; font-weight:700;">
                                        <i class="{{ $pf[2] }}"></i> {{ $pf[0] }}
                                    </span>
                                </td>

                                {{-- ลูกค้า --}}
                                <td style="padding:11px 12px; color:var(--ink);">{{ $bill->facebook_user_name ?? '-' }}</td>

                                {{-- แพคเกจ --}}
                                <td style="padding:11px 12px; white-space:nowrap;">
                                    <span class="tp-pill tp-pill-soft">{{ $bill->getReadingTypeLabel() }}</span>
                                </td>

                                {{-- สถานะ --}}
                                <td style="padding:11px 12px;">
                                    <span class="tp-pill" style="{{ $pill[1] }}" title="{{ $cStatus }}">{{ $pill[0] }}</span>
                                    <span style="display:block; font-family:monospace; font-size:10px; color:var(--ink2); margin-top:3px;">{{ $cStatus }}</span>
                                </td>

                                {{-- ค่าครู --}}
                                <td style="padding:11px 12px; white-space:nowrap;">
                                    @if($isPaid)
                                        <span style="color:#5aa07e; font-weight:600;">฿{{ number_format((float) ($bill->amount_received ?? $bill->amount_paid), 0) }} ✓</span>
                                        {{-- แสดงยอดบิลเทียบเฉพาะตอนที่ "มียอดบิลจริง" — บางบิลเก่า amount_paid=0
                                             แต่ได้เงินจริง (ตัดผ่าน SMS/แอดมิน) การโชว์ "บิล ฿0" คือขยะสายตา --}}
                                        @if($bill->amount_paid > 0 && $bill->amount_received !== null && abs((float) $bill->amount_received - (float) $bill->amount_paid) >= 0.01)
                                            <span style="display:block; font-size:10px; color:var(--ink2);">บิล ฿{{ number_format((float) $bill->amount_paid, 0) }}</span>
                                        @endif
                                    @else
                                        <span style="color:var(--ink2);">{{ $bill->amount_paid > 0 ? '฿'.number_format((float) $bill->amount_paid, 0) : '—' }}</span>
                                        <span style="display:block; font-size:10px; color:var(--ink2);">
                                            {{ ($bill->isCancelled() || $cStatus === \App\Models\FortuneReading::STATUS_COMPLETED) ? 'ไม่ได้ชำระ' : 'รอชำระ' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- เวลา --}}
                                <td style="padding:11px 12px; font-size:12px; color:var(--ink2); white-space:nowrap;">
                                    {{ $bill->created_at?->format('d/m/y H:i') }}
                                    @if(! $isPaid && in_array($cStatus, \App\Models\FortuneReading::PENDING_DISPLAY_STATUSES, true) && $bill->created_at)
                                        {{-- ⏱️ อายุบิลที่ยังรอเงิน — แอดมินจะได้รู้ว่าใบไหนควรตามก่อน --}}
                                        @php $tpAgeDays = (int) $bill->created_at->diffInDays(now()); @endphp
                                        <span style="display:block; font-size:10px; font-weight:700; color:{{ $tpAgeDays >= 3 ? '#d9534f' : ($tpAgeDays >= 1 ? '#a9791a' : '#5aa07e') }};">
                                            รอมา {{ $tpAgeDays > 0 ? $tpAgeDays.' วัน' : 'วันนี้' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- จัดการ --}}
                                <td style="padding:11px 12px; text-align:right; white-space:nowrap;">
                                    <div style="display:flex; justify-content:flex-end; align-items:center; gap:9px; flex-wrap:wrap;">

                                        @if(Route::has('admin.fortune.readings.show'))
                                            <a href="{{ route('admin.fortune.readings.show', $bill) }}" style="color:#5689b8; text-decoration:none; font-weight:600;" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i> ดู
                                            </a>
                                        @endif

                                        @if(! $isPaid && Route::has('admin.fortune.debug-tools.index'))
                                            {{-- 🔀 สลับแพคเกจ 39 ↔ 99 — เครื่องมืออยู่ที่ Debug Tools
                                                 ลิงก์แนบเลขบิลไปให้ หน้าปลายทางโหลดบิลเองไม่ต้องพิมพ์ซ้ำ
                                                 โชว์เฉพาะบิลที่ยังไม่จ่าย (จ่ายแล้วสลับ = ต้องคิดเรื่องเงินส่วนต่าง
                                                 ให้ไปทำที่หน้าเครื่องมือซึ่งมีตัวเลือก charge/free ครบ) --}}
                                            <a href="{{ route('admin.fortune.debug-tools.index', ['bill' => $bill->bill_reference ?? $bill->id]) }}"
                                               style="color:#b79ae8; text-decoration:none; font-weight:600;" title="สลับแพคเกจ Deep 39 ↔ Celtic 99">
                                                <i class="fas fa-right-left"></i> เปลี่ยนแพคเกจ
                                            </a>
                                        @endif

                                        @if(Route::has('admin.fortune.readings.edit'))
                                            <a href="{{ route('admin.fortune.readings.edit', $bill) }}" style="color:#8c8c96; text-decoration:none; font-weight:600;" title="แก้ไขข้อมูล reading">
                                                <i class="fas fa-pen"></i> แก้ไข
                                            </a>
                                        @endif

                                        @if($isCeltic && Route::has('admin.fortune.celtic-cross.show'))
                                            {{-- 🔮 กล่องเครื่องมือ Celtic ครบชุด (reset / ต่อเวลา / คืนแชท / อนุมัติ) อยู่ในหน้านี้อยู่แล้ว --}}
                                            <a href="{{ route('admin.fortune.celtic-cross.show', $bill) }}" style="color:#7b50a8; text-decoration:none; font-weight:600;" title="เครื่องมือจัดการ Celtic">
                                                <i class="fas fa-wand-sparkles"></i> จัดการ Celtic
                                            </a>
                                        @endif

                                        @if(! $isPaid && ! $isCeltic && Route::has('admin.fortune.billing.manual-confirm'))
                                            {{-- ⚠️ manualConfirm ไม่รองรับ Celtic (ไม่มี getCollectedQuestions) — Celtic ต้องใช้ force-approve --}}
                                            <button type="button"
                                                    onclick="tpBillConfirm('{{ route('admin.fortune.billing.manual-confirm', $bill) }}', {{ (float) $bill->amount_paid }}, '{{ $bill->bill_reference ?? '#'.$bill->id }}')"
                                                    style="background:none; border:0; cursor:pointer; color:#5aa07e; font-size:13px; font-weight:600;">
                                                <i class="fas fa-check"></i> ยืนยันชำระ
                                            </button>
                                        @endif

                                        @if(! $isPaid && $isCeltic && Route::has('admin.fortune.celtic-cross.force-approve'))
                                            <form action="{{ route('admin.fortune.celtic-cross.force-approve', $bill) }}" method="POST" style="display:inline;"
                                                  onsubmit="return confirm('อนุมัติบิล {{ $bill->bill_reference ?? '#'.$bill->id }} ว่าชำระแล้ว? ระบบจะเริ่มเปิดไพ่ให้ลูกค้าทันที');">
                                                @csrf
                                                <button type="submit" style="background:none; border:0; cursor:pointer; color:#5aa07e; font-size:13px; font-weight:600;">
                                                    <i class="fas fa-check-double"></i> อนุมัติ
                                                </button>
                                            </form>
                                        @endif

                                        @if($isPaid && $isDeep && empty($bill->deep_response) && ! empty($bill->getCollectedQuestions()) && Route::has('admin.fortune.billing.retry-fortune'))
                                            <form action="{{ route('admin.fortune.billing.retry-fortune', $bill) }}" method="POST" style="display:inline;"
                                                  onsubmit="return confirm('ส่งคำทำนายให้ลูกค้าอีกครั้ง?');">
                                                @csrf
                                                <button type="submit" style="background:none; border:0; cursor:pointer; color:#b79ae8; font-size:13px; font-weight:600;">
                                                    <i class="fas fa-wand-magic-sparkles"></i> ส่งคำทำนาย
                                                </button>
                                            </form>
                                        @endif

                                        @if($isPaid && ! $isCeltic && ! $bill->is_floating && Route::has('admin.fortune.billing.void'))
                                            <form action="{{ route('admin.fortune.billing.void', $bill) }}" method="POST" style="display:inline;"
                                                  onsubmit="return confirm('⛔ ยกเลิกบิล {{ $bill->bill_reference ?? '#'.$bill->id }} ที่ชำระแล้ว?');">
                                                @csrf
                                                <button type="submit" style="background:none; border:0; cursor:pointer; color:#d9534f; font-size:13px; font-weight:600;">
                                                    <i class="fas fa-ban"></i> ยกเลิกบิล
                                                </button>
                                            </form>
                                        @endif

                                        @if($isPaid && $isCeltic && Route::has('admin.fortune.celtic-cross.void-approval'))
                                            @php $tpConsumed = ($bill->getCelticPickedCount() > 0) || ((int) ($bill->celtic_questions_used ?? 0) > 0); @endphp
                                            <form action="{{ route('admin.fortune.celtic-cross.void-approval', $bill) }}" method="POST" style="display:inline;"
                                                  onsubmit="return confirm('⛔ ยกเลิกการอนุมัติบิล {{ $bill->bill_reference ?? '#'.$bill->id }} ? คืนเป็นยังไม่จ่าย + ดึงคืนคอมมิชชั่น @if($tpConsumed)(⚠️ ลูกค้าเปิดไพ่/ถามไปแล้ว) @endif');">
                                                @csrf
                                                @if($tpConsumed)<input type="hidden" name="force" value="1">@endif
                                                <button type="submit" style="background:none; border:0; cursor:pointer; color:#d9534f; font-size:13px; font-weight:600;">
                                                    <i class="fas fa-ban"></i> ยกเลิกอนุมัติ
                                                </button>
                                            </form>
                                        @endif

                                        @if($bill->is_floating && Route::has('admin.fortune.billing.floating-bills'))
                                            <a href="{{ route('admin.fortune.billing.floating-bills', ['search' => $bill->sender_info]) }}"
                                               style="color:#d6824a; text-decoration:none; font-weight:600;" title="ระบุเจ้าของบิลลอย">
                                                <i class="fas fa-user-plus"></i> ระบุเจ้าของ
                                            </a>
                                        @endif

                                        @if($bill->payment_method === \App\Models\FortuneReading::PAYMENT_METHOD_STRIPE)
                                            {{-- 💳 เครื่องมือ Stripe ครบชุดในหน้านี้เลย
                                                 เดิมลิงก์ไปหน้า billing แต่หน้านั้นถูก redirect มาที่นี่แล้ว = ลิงก์วนกลับ --}}
                                            @if($isPaid && Route::has('admin.fortune.billing.stripe-refund'))
                                                <button type="button"
                                                        onclick="tpStripeRefund('{{ route('admin.fortune.billing.stripe-refund', $bill) }}', '{{ $bill->bill_reference ?? '#'.$bill->id }}')"
                                                        style="background:none; border:0; cursor:pointer; color:#635bff; font-size:13px; font-weight:600;">
                                                    <i class="fas fa-money-bill-transfer"></i> คืนเงิน
                                                </button>
                                            @endif
                                            @if(! $isPaid && Route::has('admin.fortune.billing.stripe-expire'))
                                                <form action="{{ route('admin.fortune.billing.stripe-expire', $bill) }}" method="POST" style="display:inline;"
                                                      onsubmit="return confirm('ปิด Stripe session ของบิล {{ $bill->bill_reference ?? '#'.$bill->id }} ? ลูกค้าจะจ่ายลิงก์เดิมไม่ได้อีก');">
                                                    @csrf
                                                    <button type="submit" style="background:none; border:0; cursor:pointer; color:#635bff; font-size:13px; font-weight:600;">
                                                        <i class="fas fa-link-slash"></i> ปิด session
                                                    </button>
                                                </form>
                                            @endif
                                            @if(Route::has('admin.fortune.billing.stripe-resync'))
                                                <form action="{{ route('admin.fortune.billing.stripe-resync', $bill) }}" method="POST" style="display:inline;"
                                                      onsubmit="return confirm('ดึงสถานะล่าสุดจาก Stripe? (ใช้ตอน webhook ตกแล้วลูกค้าจ่ายแล้วระบบไม่รู้)');">
                                                    @csrf
                                                    <button type="submit" style="background:none; border:0; cursor:pointer; color:#635bff; font-size:13px; font-weight:600;">
                                                        <i class="fas fa-rotate"></i> sync Stripe
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:16px;">
                {{ $bills->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ===== Modal: ยืนยันชำระด้วยตนเอง ===== --}}
<div id="tpBillConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
    <div class="tp-card tp-raise" style="max-width:430px; width:100%; margin:0 16px; padding:24px;">
        <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 6px;">
            <i class="fas fa-check-circle" style="color:#5aa07e;"></i> ยืนยันการชำระเงินด้วยตนเอง
        </h3>
        <p style="font-size:12.5px; color:var(--ink2); margin:0 0 16px;">บิล <span id="tpBillConfirmRef" style="font-family:monospace;"></span></p>
        <form id="tpBillConfirmForm" method="POST">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ยอดเงินที่ได้รับ (บาท)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="number" name="amount" id="tpBillConfirmAmount" step="0.01" required
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมายเหตุ (ถ้ามี)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="note" maxlength="500" placeholder="เช่น ลูกค้าโอนผิดบัญชี ตรวจแล้วเงินเข้าจริง"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>
            <div style="display:flex; gap:9px; justify-content:flex-end;">
                <button type="button" class="tp-btn" onclick="tpBillConfirmClose()">ยกเลิก</button>
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-check"></i> ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Modal: คืนเงิน Stripe ===== --}}
<div id="tpStripeRefundModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
    <div class="tp-card tp-raise" style="max-width:430px; width:100%; margin:0 16px; padding:24px;">
        <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 6px;">
            <i class="fas fa-money-bill-transfer" style="color:#635bff;"></i> คืนเงิน Stripe
        </h3>
        <p style="font-size:12.5px; color:#d9534f; margin:0 0 14px;">
            <i class="fas fa-triangle-exclamation"></i> คืนเงินแล้วย้อนกลับไม่ได้ — ตรวจให้แน่ใจก่อน
        </p>
        <p style="font-size:12.5px; color:var(--ink2); margin:0 0 14px;">บิล <span id="tpStripeRefundRef" style="font-family:monospace;"></span></p>
        <form id="tpStripeRefundForm" method="POST">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">จำนวนเงิน (เว้นว่าง = คืนเต็มจำนวน)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="number" name="amount" step="0.01" min="0.01"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เหตุผล <span style="color:#d9534f;">*</span></label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="reason" required rows="3" maxlength="500" placeholder="เช่น ลูกค้าขอเงินคืนเพราะ..."
                              style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; resize:vertical;"></textarea>
                </div>
            </div>
            <div style="display:flex; gap:9px; justify-content:flex-end;">
                <button type="button" class="tp-btn" onclick="document.getElementById('tpStripeRefundModal').style.display='none'">ยกเลิก</button>
                <button type="submit" class="tp-btn tp-btn-primary" style="background:#635bff;"><i class="fas fa-money-bill-transfer"></i> ยืนยันคืนเงิน</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function tpStripeRefund(action, ref) {
        document.getElementById('tpStripeRefundForm').action = action;
        document.getElementById('tpStripeRefundRef').textContent = ref;
        document.getElementById('tpStripeRefundModal').style.display = 'flex';
    }

    function tpCopy(el, text) {
        navigator.clipboard.writeText(text).then(function () {
            var old = el.style.color;
            el.style.color = '#5aa07e';
            el.title = 'คัดลอกแล้ว ✓';
            setTimeout(function () { el.style.color = old; el.title = 'คลิกเพื่อคัดลอกเลขบิล'; }, 900);
        });
    }

    function tpBillConfirm(action, amount, ref) {
        document.getElementById('tpBillConfirmForm').action = action;
        document.getElementById('tpBillConfirmAmount').value = amount > 0 ? amount : '';
        document.getElementById('tpBillConfirmRef').textContent = ref;
        document.getElementById('tpBillConfirmModal').style.display = 'flex';
    }

    function tpBillConfirmClose() {
        document.getElementById('tpBillConfirmModal').style.display = 'none';
    }
</script>
@endpush
@endsection
