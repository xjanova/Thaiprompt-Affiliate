@extends('layouts.admin-v4')

@section('title', 'ประวัติการตรวจสลิป (SlipOK)')

@php
    // 🎨 V4 — สี pill ตามผลการตรวจ (คืน [bg, color] อ้างจาก STATUS palette นวลทองคำ)
    //   เขียว=ผ่าน / แดง=ซ้ำ / ส้ม=reject·error / เทา=ไม่ส่งตรวจ / ฟ้า=no_bill อื่นๆ
    $decisionStyle = function ($decision) {
        return match (true) {
            $decision === 'approve' => 'background:rgba(90,160,126,.18); color:#3f7a5c;',
            in_array($decision, ['duplicate', 'no_bill_duplicate']) => 'background:rgba(217,83,79,.16); color:#d9534f;',
            in_array($decision, ['reject_receiver', 'reject_amount', 'stale', 'error', 'no_bill_wrong_receiver']) => 'background:rgba(214,130,74,.18); color:#b06330;',
            in_array($decision, ['no_qr', 'not_slip', 'no_bill_unverified', 'no_slip_found']) => 'background:rgba(154,143,124,.18); color:var(--ink2);',
            str_starts_with((string) $decision, 'no_bill') => 'background:rgba(86,137,184,.18); color:#3f6a96;',
            default => 'background:rgba(154,143,124,.18); color:var(--ink2);',
        };
    };
@endphp

@section('content')
{{-- 🧾 ประวัติการตรวจสลิป SlipOK (ธีม V4 นวลทองคำ) — คงทุก filter/field/route เดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ประวัติตรวจสลิป</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ประวัติการตรวจสลิป 🧾</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ทุกการตรวจสลิป — ส่งไปเช็คจริงไหม / สลิปซ้ำไหม / บิลไหน</div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.celtic-cross.index'))
                <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> Celtic Cross
                </a>
            @endif
            @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gear"></i> ตั้งค่า SlipOK
                </a>
            @endif
        </div>
    </div>

    {{-- ===== 📊 โควตา SlipOK คงเหลือ (2026-06-05) — เห็นชัดว่าเหลือยิงตรวจได้กี่ครั้ง ===== --}}
    @php
        $q = $slipokQuota ?? [];
        $qOk = (bool) ($q['success'] ?? false);
        $qLeft = (int) ($q['quota'] ?? 0);
        // โทนสีตามจำนวนคงเหลือ: < 50 = แดง (ใกล้หมด) / < 200 = เหลือง / ปกติ = เขียว / เช็คไม่ได้ = กลาง
        $qTone = ! $qOk ? 'gray' : ($qLeft < 50 ? 'red' : ($qLeft < 200 ? 'amber' : 'green'));
        $qAccent = [
            'green' => '#5aa07e',
            'amber' => '#e0a52e',
            'red'   => '#d9534f',
            'gray'  => '#9a8f7c',
        ][$qTone];
    @endphp
    <div class="tp-card" style="padding:18px; border-left:4px solid {{ $qAccent }};">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="tp-tile" style="width:46px; height:46px; border-radius:13px; font-size:20px; display:flex; align-items:center; justify-content:center; background:{{ $qAccent }}; flex-shrink:0;">
                    <i class="fas fa-gauge"></i>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2);">โควตา SlipOK คงเหลือ (ตรวจสลิปได้อีก)</div>
                    @if ($qOk)
                        <div style="display:flex; align-items:baseline; gap:7px; margin-top:2px;">
                            <span class="tp-num" style="font-size:30px; font-weight:800; line-height:1; color:{{ $qAccent }};">{{ number_format($qLeft) }}</span>
                            <span style="font-size:13px; color:var(--ink2);">ครั้ง</span>
                            @if ($qTone === 'red')
                                <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; margin-left:4px;">⚠️ ใกล้หมด</span>
                            @endif
                        </div>
                    @else
                        <div style="font-size:13.5px; font-weight:600; color:var(--ink); margin-top:3px;">
                            เช็คโควตาไม่ได้ — {{ $q['message'] ?? 'เชื่อมต่อ SlipOK ไม่สำเร็จ' }}
                        </div>
                    @endif
                </div>
            </div>
            <div style="font-size:11.5px; color:var(--ink2); text-align:right; display:flex; flex-direction:column; gap:2px; align-items:flex-end;">
                @if ($qOk)
                    @if (! empty($q['overQuota']))
                        <div>เกินโควตา (over): <span style="font-weight:700; color:var(--ink);">{{ number_format((int) $q['overQuota']) }}</span></div>
                    @endif
                    @if (! empty($q['specialQuota']))
                        <div>โควตาพิเศษ: <span style="font-weight:700; color:var(--ink);">{{ number_format((int) $q['specialQuota']) }}</span></div>
                    @endif
                    @if (! empty($q['endDate']))
                        <div>หมดรอบ: <span style="font-weight:700; color:var(--ink);">{{ $q['endDate'] }}</span></div>
                    @endif
                @elseif (Route::has('admin.fortune.settings.index'))
                    <a href="{{ route('admin.fortune.settings.index') }}" style="color:var(--deep1); font-weight:700; text-decoration:underline;">→ ตั้งค่า SlipOK</a>
                @endif
                <div style="font-size:10.5px; color:var(--ink2); opacity:.7;">อัปเดตทุก 5 นาที</div>
            </div>
        </div>
    </div>

    {{-- ===== KPI grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @php
            // [label, value, icon, สีไอคอน]
            $kpis = [
                ['ทั้งหมด', $stats['total'], 'fa-layer-group', null],
                ['วันนี้', $stats['today'], 'fa-calendar-day', '#5689b8'],
                ['ส่ง SlipOK จริง', $stats['sent_slipok'], 'fa-paper-plane', '#b79ae8'],
                ['สลิปซ้ำ', $stats['duplicates'], 'fa-copy', '#d9534f'],
                ['ผ่าน (approve)', $stats['approved'], 'fa-circle-check', '#5aa07e'],
            ];
        @endphp
        @foreach ($kpis as [$label, $value, $icon, $iconBg])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;@if($iconBg) background:{{ $iconBg }};@endif">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Filters ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
        <form method="GET" style="display:flex; flex-direction:column; gap:16px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                {{-- ค้นหา --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">🔍 ค้นหา (user / transRef / ชื่อ / บิล)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="search" value="{{ $filters['search'] }}"
                               placeholder="ค้นหา user / transRef / ชื่อ / บิล"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                {{-- ผลการตรวจ (decision) --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ผลการตรวจ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="decision" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="">— ผลทั้งหมด —</option>
                            @foreach ($decisions as $d)
                                <option value="{{ $d }}" @selected($filters['decision'] === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- platform --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Platform</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="">— ทุก platform —</option>
                            <option value="line" @selected($filters['platform'] === 'line')>LINE</option>
                            <option value="facebook" @selected($filters['platform'] === 'facebook')>Facebook</option>
                        </select>
                    </div>
                </div>

                {{-- วันที่เริ่มต้น --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่เริ่มต้น</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                {{-- วันที่สิ้นสุด --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่สิ้นสุด</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>
            </div>

            {{-- checkbox filters --}}
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px;">
                <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:var(--ink); cursor:pointer;">
                    <input type="checkbox" name="dup_only" value="1" @checked($filters['dup_only']) style="width:16px; height:16px; accent-color:#d9534f; cursor:pointer;">
                    เฉพาะสลิปซ้ำ
                </label>
                <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:var(--ink); cursor:pointer;">
                    <input type="checkbox" name="sent_only" value="1" @checked($filters['sent_only']) style="width:16px; height:16px; accent-color:#b79ae8; cursor:pointer;">
                    เฉพาะที่ส่ง SlipOK จริง
                </label>
            </div>

            {{-- ปุ่ม action --}}
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fortune.slip-logs.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Table ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        @php
                            $thBase = 'padding:13px 14px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
                        @endphp
                        <th style="{{ $thBase }} text-align:left;">เวลา</th>
                        <th style="{{ $thBase }} text-align:left;">บิล</th>
                        <th style="{{ $thBase }} text-align:left;">ลูกค้า</th>
                        <th style="{{ $thBase }} text-align:center;">ส่ง SlipOK</th>
                        <th style="{{ $thBase }} text-align:left;">ผล</th>
                        <th style="{{ $thBase }} text-align:center;">ซ้ำ</th>
                        <th style="{{ $thBase }} text-align:right;">ยอด</th>
                        <th style="{{ $thBase }} text-align:left;">transRef</th>
                        <th style="{{ $thBase }} text-align:left;">บัญชีปลายทาง</th>
                        <th style="{{ $thBase }} text-align:left;">ผู้โอน</th>
                        <th style="{{ $thBase }} text-align:center;">รูปสลิป<br><span style="font-size:9px; text-transform:none; letter-spacing:0; opacity:.7;">ที่ส่งไปตรวจ</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        {{-- 🟡 แต่ละแถวมี x-data เอง (Alpine v3 init เฉพาะ subtree ที่มี root) — เผื่อ interaction --}}
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- เวลา --}}
                            <td style="padding:13px 14px; white-space:nowrap; color:var(--ink2); font-size:12px;">
                                <div style="color:var(--ink);">{{ $log->created_at?->format('d/m H:i:s') }}</div>
                                <div style="font-size:10.5px; color:var(--ink2);">{{ $log->context }}</div>
                            </td>
                            {{-- บิล --}}
                            <td style="padding:13px 14px; white-space:nowrap;">
                                @if ($log->fortune_reading_id)
                                    @if(Route::has('admin.fortune.celtic-cross.show'))
                                        <a href="{{ route('admin.fortune.celtic-cross.show', $log->fortune_reading_id) }}"
                                           style="color:var(--deep1); font-weight:700; text-decoration:none;">#{{ $log->fortune_reading_id }}</a>
                                    @else
                                        <span style="color:var(--ink); font-weight:700;">#{{ $log->fortune_reading_id }}</span>
                                    @endif
                                    <div style="font-size:10.5px; color:var(--ink2);">{{ $log->reading?->bill_reference }}</div>
                                @else
                                    <span style="color:var(--ink2); font-size:12px;">ไม่มีบิล</span>
                                @endif
                            </td>
                            {{-- ลูกค้า --}}
                            <td style="padding:13px 14px;">
                                <div style="color:var(--ink); font-size:12.5px;">{{ $log->reading?->facebook_user_name ?? '—' }}</div>
                                <div style="font-size:10.5px; color:var(--ink2); text-transform:uppercase;">{{ $log->platform }}</div>
                            </td>
                            {{-- ส่ง SlipOK --}}
                            <td style="padding:13px 14px; text-align:center;">
                                @if ($log->sent_to_slipok)
                                    <span style="color:#5aa07e; font-size:16px;" title="ส่งไป SlipOK API จริง"><i class="fas fa-circle-check"></i></span>
                                @else
                                    <span style="color:var(--ink2); opacity:.5;" title="ไม่ได้ส่ง (pre-check ตัด / ไม่มีสลิป)">—</span>
                                @endif
                            </td>
                            {{-- ผล --}}
                            <td style="padding:13px 14px; white-space:nowrap;">
                                <span class="tp-pill" style="{{ $decisionStyle($log->decision) }}">{{ $log->decision }}</span>
                                @if ($log->to_our_account === true)
                                    <div style="font-size:10px; color:#5aa07e; margin-top:3px;">เข้าบัญชีร้าน</div>
                                @elseif ($log->to_our_account === false)
                                    <div style="font-size:10px; color:#d6824a; margin-top:3px;">บัญชีอื่น</div>
                                @endif
                            </td>
                            {{-- ซ้ำ --}}
                            <td style="padding:13px 14px; text-align:center;">
                                @if ($log->is_duplicate)
                                    <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:800;">ซ้ำ</span>
                                @else
                                    <span style="color:var(--ink2); opacity:.5;">—</span>
                                @endif
                            </td>
                            {{-- ยอด --}}
                            <td class="tp-num" style="padding:13px 14px; text-align:right; white-space:nowrap; color:var(--ink); font-weight:600;">
                                {{ $log->amount ? '฿'.number_format((float) $log->amount, 2) : '—' }}
                            </td>
                            {{-- transRef --}}
                            <td style="padding:13px 14px; font-family:monospace; font-size:10.5px; color:var(--ink2); word-break:break-all; max-width:140px;">
                                {{ $log->trans_ref ?? '—' }}
                            </td>
                            {{-- บัญชีปลายทาง --}}
                            <td style="padding:13px 14px; font-size:12px; color:var(--ink2);">{{ $log->receiver_account ?? '—' }}</td>
                            {{-- ผู้โอน --}}
                            <td style="padding:13px 14px; font-size:12px; color:var(--ink2);">{{ $log->sender_name ?? '—' }}</td>
                            {{-- รูปสลิปที่ส่งไปตรวจ --}}
                            <td style="padding:13px 14px; text-align:center;">
                                @if ($log->slip_image_path)
                                    {{-- ⚠️ (2026-06-05) onerror: ถ้าไฟล์ถูกลบ (404 — purge 30 วัน / โดน deploy เก่าลบ)
                                         แทนที่ลิงก์ด้วยป้าย "🚫 ลบแล้ว" แทนไอคอนรูปแตก ให้แอดมินเข้าใจว่าไฟล์หาย ไม่ใช่ระบบพัง --}}
                                    <a href="{{ route('admin.fortune.slip-logs.image', $log->id) }}" target="_blank" rel="noopener"
                                       title="เปิดรูปสลิปที่ส่งไปตรวจ (ดูว่า QR ชัดไหม)">
                                        <img src="{{ route('admin.fortune.slip-logs.image', $log->id) }}" loading="lazy" alt="slip"
                                             onerror="this.onerror=null;var s=document.createElement('span');s.style.cssText='font-size:10px;color:var(--ink2);opacity:.6;';s.title='ไฟล์รูปถูกลบแล้ว (เกิน 30 วัน หรือถูก deploy ลบก่อนแพตช์ 2026-06-05)';s.textContent='🚫 ลบแล้ว';this.closest('a').replaceWith(s);"
                                             style="height:48px; width:48px; object-fit:cover; border-radius:9px; box-shadow:var(--inset-sm); display:inline-block; transition:transform .18s, box-shadow .18s; transform-origin:center; position:relative; z-index:1;"
                                             onmouseover="this.style.transform='scale(2.5)'; this.style.boxShadow='var(--raise)'; this.style.zIndex='30'; this.style.position='relative';"
                                             onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='var(--inset-sm)'; this.style.zIndex='1';">
                                    </a>
                                @else
                                    <span style="color:var(--ink2); opacity:.5;" title="ไม่มีรูป (ไม่ได้เก็บ / purge หลัง 30 วัน)">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="padding:0;">
                                {{-- Empty state --}}
                                <div style="text-align:center; color:var(--ink2); padding:48px 0;">
                                    <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    ยังไม่มีประวัติการตรวจสลิป
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($logs->hasPages())
        <div>
            {{ $logs->links() }}
        </div>
    @endif

</div>
@endsection
