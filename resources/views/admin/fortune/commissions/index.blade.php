{{--
    ภาพรวมคอมมิชชั่นดูดวง (Fortune Commission Overview)
    ธีม V4 "นวลทองคำ" (Nuan Gold Clay)

    ตัวแปรจาก FortuneCommissionController@index:
    - $commissions : paginator ของ FortuneCommission (with user, fromUser, reading)
    - $stats       : ['total_count','total_amount','l1_count','l1_amount','l2_count','l2_amount',
                      'pending_count','pending_amount','approved_count','approved_amount',
                      'paid_count','paid_amount','rejected_count']
    - $filters     : ['status','level','date_from','date_to','search']

    คงฟังก์ชันเดิม 100%:
    - ฟอร์มกรอง GET admin.fortune.commissions.index (status, level, date_from, date_to)
    - ฟอร์มค้นหา GET (search) + hidden fields ทุกตัวยกเว้น search/page
    - ลิงก์ admin.fortune.commissions.manage / .export (พร้อม query เดิม)
    - AJAX modal: GET admin/fortune/commissions/{id} (X-Requested-With) แสดงรายละเอียด
    - pagination ->appends(request()->query())
    กราฟ = CSS (ห้าม Chart.js)
--}}
@extends('layouts.admin-v4')

@section('title', 'ภาพรวมคอมมิชชั่นดูดวง')

@section('content')
{{-- container หลัก --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · คอมมิชชั่น
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                ภาพรวมคอมมิชชั่นดูดวง 💰
            </h1>
            <p class="tp-muted" style="font-size:13px; margin:6px 0 0; max-width:560px;">
                สถิติและประวัติคอมมิชชั่นจากระบบแนะนำดูดวง — L1 สายตรง / L2 ชั้นหลาน
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.commissions.manage'))
                <a href="{{ route('admin.fortune.commissions.manage') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-sliders"></i> จัดการคอมมิชชั่น
                </a>
            @endif
            @if(Route::has('admin.fortune.commissions.export'))
                <a href="{{ route('admin.fortune.commissions.export', request()->query()) }}" class="tp-btn">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI GRID (Primary) ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        {{-- รวมทั้งหมด --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--accent1);">
                <i class="fas fa-gem"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1); line-height:1;">
                    ฿{{ number_format($stats['total_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    รวมทั้งหมด · {{ number_format($stats['total_count']) }} รายการ
                </div>
            </div>
        </div>

        {{-- L1 สายตรง --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#5689b8;">
                <i class="fas fa-user"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#5689b8; line-height:1;">
                    ฿{{ number_format($stats['l1_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    L1 สายตรง · {{ number_format($stats['l1_count']) }} รายการ
                </div>
            </div>
        </div>

        {{-- L2 ชั้นหลาน --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#b79ae8;">
                <i class="fas fa-users"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#b79ae8; line-height:1;">
                    ฿{{ number_format($stats['l2_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    L2 ชั้นหลาน · {{ number_format($stats['l2_count']) }} รายการ
                </div>
            </div>
        </div>

        {{-- รอดำเนินการ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#e0a52e;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#e0a52e; line-height:1;">
                    ฿{{ number_format($stats['pending_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    รอดำเนินการ · {{ number_format($stats['pending_count']) }} รายการ
                </div>
            </div>
        </div>

        {{-- อนุมัติแล้ว --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#5aa07e;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e; line-height:1;">
                    ฿{{ number_format($stats['approved_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    อนุมัติแล้ว · {{ number_format($stats['approved_count']) }} รายการ
                </div>
            </div>
        </div>

        {{-- จ่ายแล้ว --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--deep2);">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep2); line-height:1;">
                    ฿{{ number_format($stats['paid_amount'], 2) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    จ่ายแล้ว · {{ number_format($stats['paid_count']) }} รายการ
                </div>
            </div>
        </div>
    </div>

    {{-- ===== กราฟ CSS: สัดส่วนชั้น + สถานะ ===== --}}
    @php
        // คำนวณค่าสำหรับกราฟ (กันหารศูนย์)
        $maxLevel       = max($stats['l1_amount'], $stats['l2_amount'], 1);
        $l1Pct          = round($stats['l1_amount'] / $maxLevel * 100);
        $l2Pct          = round($stats['l2_amount'] / $maxLevel * 100);

        // โดนัทสถานะ (นับจำนวนรายการ)
        $statusTotal    = max($stats['pending_count'] + $stats['approved_count'] + $stats['paid_count'] + $stats['rejected_count'], 1);
        $pendingPct     = $stats['pending_count']  / $statusTotal * 100;
        $approvedPct    = $stats['approved_count'] / $statusTotal * 100;
        $paidPct        = $stats['paid_count']     / $statusTotal * 100;
        $rejectedPct    = $stats['rejected_count'] / $statusTotal * 100;

        // จุดตัดของ conic-gradient (สะสม)
        $s1 = $pendingPct;
        $s2 = $s1 + $approvedPct;
        $s3 = $s2 + $paidPct;
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- กราฟแท่ง: คอมมิชชั่นตามชั้น --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:18px; display:flex; align-items:center; gap:9px;">
                <i class="fas fa-layer-group" style="color:var(--accent1);"></i> คอมมิชชั่นตามชั้น
            </div>

            {{-- L1 --}}
            <div style="margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:7px;">
                    <span style="font-size:13px; color:var(--ink); font-weight:600;">L1 สายตรง</span>
                    <span class="tp-num" style="font-size:13px; color:#5689b8; font-weight:700;">
                        ฿{{ number_format($stats['l1_amount'], 2) }}
                    </span>
                </div>
                <div class="tp-inset-sm" style="height:14px; border-radius:99px; overflow:hidden;">
                    <div style="height:100%; width:{{ $l1Pct }}%; border-radius:99px; background:linear-gradient(90deg,#5689b8,#5aa07e); transition:width .4s ease;"></div>
                </div>
            </div>

            {{-- L2 --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:7px;">
                    <span style="font-size:13px; color:var(--ink); font-weight:600;">L2 ชั้นหลาน</span>
                    <span class="tp-num" style="font-size:13px; color:#b79ae8; font-weight:700;">
                        ฿{{ number_format($stats['l2_amount'], 2) }}
                    </span>
                </div>
                <div class="tp-inset-sm" style="height:14px; border-radius:99px; overflow:hidden;">
                    <div style="height:100%; width:{{ $l2Pct }}%; border-radius:99px; background:linear-gradient(90deg,#b79ae8,var(--accent1)); transition:width .4s ease;"></div>
                </div>
            </div>
        </div>

        {{-- โดนัท: สัดส่วนสถานะ --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:18px; display:flex; align-items:center; gap:9px;">
                <i class="fas fa-chart-pie" style="color:var(--accent1);"></i> สัดส่วนสถานะ (ตามจำนวนรายการ)
            </div>
            <div style="display:flex; align-items:center; gap:22px; flex-wrap:wrap;">
                {{-- โดนัท conic-gradient --}}
                <div style="position:relative; width:128px; height:128px; flex-shrink:0;">
                    <div style="width:100%; height:100%; border-radius:50%;
                        background:conic-gradient(
                            #e0a52e 0% {{ $s1 }}%,
                            #5aa07e {{ $s1 }}% {{ $s2 }}%,
                            #5689b8 {{ $s2 }}% {{ $s3 }}%,
                            #d9534f {{ $s3 }}% 100%
                        );">
                    </div>
                    {{-- รูตรงกลาง --}}
                    <div class="tp-card" style="position:absolute; inset:26px; border-radius:50%; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1); line-height:1;">
                            {{ number_format($stats['total_count']) }}
                        </div>
                        <div style="font-size:10px; color:var(--ink2); margin-top:2px;">รายการ</div>
                    </div>
                </div>
                {{-- legend --}}
                <div style="display:flex; flex-direction:column; gap:9px; font-size:12.5px; min-width:130px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:11px; height:11px; border-radius:3px; background:#e0a52e; flex-shrink:0;"></span>
                        <span style="color:var(--ink);">รอดำเนินการ</span>
                        <span class="tp-num" style="margin-left:auto; color:var(--ink2); font-weight:700;">{{ number_format($stats['pending_count']) }}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:11px; height:11px; border-radius:3px; background:#5aa07e; flex-shrink:0;"></span>
                        <span style="color:var(--ink);">อนุมัติแล้ว</span>
                        <span class="tp-num" style="margin-left:auto; color:var(--ink2); font-weight:700;">{{ number_format($stats['approved_count']) }}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:11px; height:11px; border-radius:3px; background:#5689b8; flex-shrink:0;"></span>
                        <span style="color:var(--ink);">จ่ายแล้ว</span>
                        <span class="tp-num" style="margin-left:auto; color:var(--ink2); font-weight:700;">{{ number_format($stats['paid_count']) }}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:11px; height:11px; border-radius:3px; background:#d9534f; flex-shrink:0;"></span>
                        <span style="color:var(--ink);">ปฏิเสธ</span>
                        <span class="tp-num" style="margin-left:auto; color:var(--ink2); font-weight:700;">{{ number_format($stats['rejected_count']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ฟอร์มกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.fortune.commissions.index') }}">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px; align-items:end;">
                {{-- สถานะ --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="status"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>ทั้งหมด</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>⏳ รอดำเนินการ</option>
                            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>✅ อนุมัติแล้ว</option>
                            <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>💵 จ่ายแล้ว</option>
                            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>❌ ปฏิเสธ</option>
                        </select>
                    </div>
                </div>

                {{-- ชั้น --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชั้น</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="level"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="all" @selected(($filters['level'] ?? 'all') === 'all')>ทั้งหมด</option>
                            <option value="1" @selected(($filters['level'] ?? '') == '1')>L1 สายตรง</option>
                            <option value="2" @selected(($filters['level'] ?? '') == '2')>L2 ชั้นหลาน</option>
                        </select>
                    </div>
                </div>

                {{-- วันที่เริ่มต้น --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่เริ่มต้น</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="tp-well tp-input">
                </div>

                {{-- วันที่สิ้นสุด --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่สิ้นสุด</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="tp-well tp-input">
                </div>

                {{-- ปุ่มกรอง / ล้าง --}}
                <div style="display:flex; gap:9px;">
                    <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center;">
                        <i class="fas fa-magnifying-glass"></i> กรอง
                    </button>
                    <a href="{{ route('admin.fortune.commissions.index') }}" class="tp-btn" style="flex:1; justify-content:center;">
                        <i class="fas fa-rotate-left"></i> ล้าง
                    </a>
                </div>
            </div>
        </form>

        <div class="tp-divider" style="margin:16px 0;"></div>

        {{-- ค้นหา (แถวที่ 2) — คงค่า filter อื่นไว้เป็น hidden เหมือนเดิม --}}
        <form method="GET" action="{{ route('admin.fortune.commissions.index') }}">
            @foreach(request()->except('search', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="ค้นหาชื่อ/email ผู้รับหรือผู้จ่าย..."
                    class="tp-well tp-input" style="flex:1; min-width:200px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- ===== ตารางคอมมิชชั่น ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($commissions->isEmpty())
            {{-- Empty state --}}
            <div style="text-align:center; color:var(--ink2); padding:48px 20px;">
                <i class="fas fa-inbox" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                ไม่พบข้อมูลคอมมิชชั่น
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:880px;">
                    <thead>
                        <tr style="text-align:left;">
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ผู้รับ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">จากผู้ใช้</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">บิล #</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ชั้น</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ประเภท</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase; text-align:right;">จำนวน (฿)</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">สถานะ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">วันที่</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase; text-align:right;">ดู</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissions as $c)
                            {{-- แต่ละแถวมี x-data เอง (Alpine v3 init เฉพาะ subtree ที่มี x-data root) --}}
                            <tr style="box-shadow:var(--inset-sm);" x-data>
                                {{-- ผู้รับ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    <div style="display:flex; align-items:center; gap:11px;">
                                        <div class="tp-tile" style="width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:800; font-size:13px; color:var(--accent1);">
                                            {{ mb_substr($c->user?->name ?? '?', 0, 1) }}
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700; color:var(--ink); font-size:13.5px;">
                                                {{ Str::limit($c->user?->name ?? '-', 20) }}
                                            </div>
                                            <div class="tp-muted" style="font-size:11.5px; word-break:break-all;">
                                                {{ $c->user?->email ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- จากผู้ใช้ --}}
                                <td style="padding:14px 16px; vertical-align:top; color:var(--ink); font-size:13.5px;">
                                    {{ Str::limit($c->fromUser?->name ?? '-', 20) }}
                                </td>

                                {{-- บิล # --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    <span class="tp-num" style="color:var(--ink); font-size:13.5px;">#{{ $c->fortune_reading_id ?? '-' }}</span>
                                </td>

                                {{-- ชั้น --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    @if($c->level === 1)
                                        <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8; font-weight:700;">L1 สายตรง</span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(183,154,232,.18); color:#b79ae8; font-weight:700;">L2 หลาน</span>
                                    @endif
                                </td>

                                {{-- ประเภท / อัตรา --}}
                                <td style="padding:14px 16px; vertical-align:top; color:var(--ink2); font-size:13px;">
                                    <span class="tp-num">{{ $c->commission_type === 'percent' ? $c->commission_rate.'%' : '฿'.$c->commission_rate }}</span>
                                </td>

                                {{-- จำนวน --}}
                                <td style="padding:14px 16px; vertical-align:top; text-align:right;">
                                    <span class="tp-num" style="font-weight:800; color:var(--deep1); font-size:14px;">
                                        ฿{{ number_format($c->amount, 2) }}
                                    </span>
                                </td>

                                {{-- สถานะ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    @switch($c->status)
                                        @case('pending')
                                            <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e; font-weight:700;">⏳ รอดำเนินการ</span>
                                            @break
                                        @case('approved')
                                            <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e; font-weight:700;">✅ อนุมัติแล้ว</span>
                                            @break
                                        @case('paid')
                                            <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8; font-weight:700;">💵 จ่ายแล้ว</span>
                                            @break
                                        @case('rejected')
                                            <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:700;">❌ ปฏิเสธ</span>
                                            @break
                                    @endswitch
                                </td>

                                {{-- วันที่ --}}
                                <td style="padding:14px 16px; vertical-align:top; color:var(--ink2); font-size:13px;">
                                    <div class="tp-num" style="color:var(--ink);">{{ $c->created_at?->format('d/m/Y') }}</div>
                                    <div class="tp-muted" style="font-size:11.5px;">{{ $c->created_at?->format('H:i') }}</div>
                                </td>

                                {{-- ดู --}}
                                <td style="padding:14px 16px; vertical-align:top; text-align:right;">
                                    <button type="button" class="tp-btn tp-btn-sm"
                                        @click="window.fortuneShowCommissionDetail({{ $c->id }})">
                                        <i class="fas fa-eye"></i> รายละเอียด
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Pagination ===== --}}
    @if($commissions->hasPages())
        <div>
            {{ $commissions->appends(request()->query())->links() }}
        </div>
    @endif
</div>

{{-- ===== Detail Modal — คุมด้วย DOM class ล้วน (ไม่ผ่าน Alpine reactive) ===== --}}
<div id="detailModal"
     style="position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1200; display:none; align-items:center; justify-content:center; padding:16px;"
     onclick="if (event.target === this) window.fortuneCloseCommissionDetail()">
    <div class="tp-card tp-raise" style="width:100%; max-width:560px; padding:24px;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px;">
                <i class="fas fa-receipt" style="color:var(--accent1);"></i> รายละเอียดคอมมิชชั่น
            </div>
            <button type="button" class="tp-icon-btn" onclick="window.fortuneCloseCommissionDetail()" aria-label="ปิด">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div id="detailContent" style="max-height:70vh; overflow-y:auto;">
            <div style="text-align:center; padding:32px 0; color:var(--ink2);">
                <i class="fas fa-spinner fa-spin" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                กำลังโหลด...
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * แสดงรายละเอียดคอมมิชชั่น (modal) — AJAX endpoint เดิม: admin/fortune/commissions/{id}
 * ผูกไว้บน window เพื่อให้ปุ่ม @click ของแต่ละแถวเรียกได้
 */
window.fortuneShowCommissionDetail = function (id) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    modal.style.display = 'flex';

    content.innerHTML = '<div style="text-align:center; padding:32px 0; color:var(--ink2);"><i class="fas fa-spinner fa-spin" style="font-size:24px; display:block; margin-bottom:10px;"></i>กำลังโหลด...</div>';

    fetch(`{{ url('admin/fortune/commissions') }}/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            content.innerHTML = '<div style="text-align:center; color:#d9534f; padding:18px 0;">โหลดข้อมูลล้มเหลว</div>';
            return;
        }
        const d = res.data;
        // กล่องย่อย — ใช้ตัวแปร theme V4 (ห้าม hardcode เทา/ขาว)
        const cell = 'padding:12px 13px; border-radius:13px; box-shadow:var(--inset-sm);';
        const lbl  = 'color:var(--ink2); font-size:11px; margin-bottom:4px;';
        const val  = 'color:var(--ink); font-weight:700; font-size:14px;';
        const sub  = 'color:var(--ink2); font-size:11.5px; margin-top:2px;';
        content.innerHTML = `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:11px;">
                <div style="grid-column:1 / -1; ${cell}">
                    <div style="${lbl}">ID / สถานะ</div>
                    <div style="${val}">#${d.id} — ${d.status_name}</div>
                </div>
                <div style="${cell}">
                    <div style="${lbl}">ผู้รับคอมมิชชั่น</div>
                    <div style="${val}">${d.user?.name ?? '-'}</div>
                    <div style="${sub}">${d.user?.email ?? ''}</div>
                </div>
                <div style="${cell}">
                    <div style="${lbl}">จากผู้ใช้ (คนดูดวง)</div>
                    <div style="${val}">${d.from_user?.name ?? '-'}</div>
                    <div style="${sub}">${d.from_user?.email ?? ''}</div>
                </div>
                <div style="${cell}">
                    <div style="${lbl}">ชั้น</div>
                    <div style="${val}">L${d.level} (${d.level_name})</div>
                </div>
                <div style="${cell}">
                    <div style="${lbl}">ประเภท / อัตรา</div>
                    <div style="${val}">${d.commission_type === 'percent' ? d.commission_rate + '%' : '฿' + d.commission_rate}</div>
                </div>
                <div style="padding:12px 13px; border-radius:13px; box-shadow:var(--inset-sm); background:var(--a1soft);">
                    <div style="color:var(--deep2); font-size:11px; margin-bottom:4px;">จำนวนเงิน</div>
                    <div style="color:var(--deep1); font-weight:800; font-size:19px;">฿${Number(d.amount).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                </div>
                <div style="${cell}">
                    <div style="${lbl}">ราคาดูดวง</div>
                    <div style="${val}">฿${Number(d.reading_price).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                </div>
                ${d.reading ? `
                <div style="grid-column:1 / -1; ${cell}">
                    <div style="${lbl}">บิลดูดวง</div>
                    <div style="${val}">#${d.reading.id} (${d.reading.reading_type}) — ชำระ ฿${Number(d.reading.amount_paid ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                    <div style="${sub}">${d.reading.created_at ?? ''}</div>
                </div>
                ` : ''}
                ${d.notes ? `
                <div style="grid-column:1 / -1; padding:12px 13px; border-radius:13px; box-shadow:var(--inset-sm); background:rgba(224,165,46,.12);">
                    <div style="color:#e0a52e; font-size:11px; margin-bottom:4px;">หมายเหตุ</div>
                    <div style="color:var(--ink); font-size:13.5px;">${d.notes}</div>
                </div>
                ` : ''}
                <div style="grid-column:1 / -1; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; font-size:11.5px; color:var(--ink2);">
                    <div>สร้าง: ${d.created_at ?? '-'}</div>
                    <div>อนุมัติ: ${d.approved_at ?? '-'}</div>
                    <div>จ่าย: ${d.paid_at ?? '-'}</div>
                </div>
            </div>
        `;
    })
    .catch(() => {
        content.innerHTML = '<div style="text-align:center; color:#d9534f; padding:18px 0;">เกิดข้อผิดพลาด กรุณาลองใหม่</div>';
    });
};

/**
 * ปิด modal รายละเอียด
 */
window.fortuneCloseCommissionDetail = function () {
    document.getElementById('detailModal').style.display = 'none';
};

// ปิด modal ด้วยปุ่ม ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('detailModal');
        if (modal && modal.style.display === 'flex') {
            window.fortuneCloseCommissionDetail();
        }
    }
});
</script>
@endpush
@endsection
