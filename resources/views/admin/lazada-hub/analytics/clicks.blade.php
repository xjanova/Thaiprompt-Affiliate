@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'บันทึกการคลิก Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — บันทึกการคลิก (ธีม V4 "นวลทองคำ")
     ตารางดิบของ marketplace_link_clicks + ฟิลเตอร์ ช่วงวันที่ / สมาชิก / เฉพาะที่แปลงแล้ว

     🔒 ความเป็นส่วนตัว: IP ถูกปิดบังชุดท้ายเสมอ (ดูพอให้แยกเครื่องได้ แต่ไม่ระบุตัวบุคคล)
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> ·
                <a href="{{ route('admin.lazada-hub.analytics.index') }}" style="color:var(--accent2);text-decoration:none;">อนาไลติก</a> ·
                บันทึกการคลิก
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-hand-pointer" style="color:var(--accent1);"></i> บันทึกการคลิก
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">
                หลักฐานว่า "ใครกดลิงก์ไหน เมื่อไหร่" — เป็นตัวเดียวที่ใช้ระบุผู้แนะนำได้ เพราะลาซาด้าไม่รองรับ subId
            </p>
        </div>
        <a href="{{ route('admin.lazada-hub.analytics.index') }}" class="tp-btn">
            <i class="fas fa-chart-line"></i> <span>กลับหน้าอนาไลติก</span>
        </a>
    </div>

    {{-- ── ไม่มีแพลตฟอร์ม lazada ── --}}
    @if(! $platform)
        <div class="tp-card" style="border-left:4px solid #d9534f;font-size:.85rem;color:var(--ink);">
            <i class="fas fa-triangle-exclamation" style="color:#d9534f;"></i>
            ยังไม่มีแพลตฟอร์ม "lazada" ในฐานข้อมูล — ตารางด้านล่างจึงว่างเสมอ
        </div>
    @endif

    {{-- ── ฟิลเตอร์ ── --}}
    <div class="tp-card">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div>
                <label class="tp-muted" style="display:block;font-size:.72rem;margin-bottom:4px;">ตั้งแต่วันที่</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="tp-input tp-num" style="width:auto;padding:8px 12px;font-size:.82rem;">
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:.72rem;margin-bottom:4px;">ถึงวันที่</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="tp-input tp-num" style="width:auto;padding:8px 12px;font-size:.82rem;">
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:.72rem;margin-bottom:4px;">สมาชิก</label>
                <select name="user_id" class="tp-input" style="width:auto;min-width:170px;padding:8px 12px;font-size:.82rem;">
                    <option value="0">ทุกคน</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected($filters['user_id'] === (int) $member->id)>
                            {{ $member->name ?: 'สมาชิก #'.$member->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:.84rem;color:var(--ink);cursor:pointer;padding-bottom:9px;">
                <input type="checkbox" name="converted" value="1" @checked($filters['converted']) style="width:17px;height:17px;accent-color:var(--accent1);">
                เฉพาะที่แปลงเป็นออเดอร์แล้ว
            </label>
            <button class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-filter"></i> <span>กรอง</span></button>
            <a href="{{ route('admin.lazada-hub.analytics.clicks') }}" class="tp-btn tp-btn-sm"><i class="fas fa-rotate-left"></i> <span>ล้าง</span></a>
        </form>

        @if(count($members) >= 200)
            <div class="tp-muted" style="font-size:.72rem;margin-top:8px;">
                * รายชื่อสมาชิกในตัวเลือกแสดงได้สูงสุด 200 คน (เรียงตามชื่อ)
            </div>
        @endif
    </div>

    {{-- ── ตาราง ── --}}
    @if($clicks->total() === 0)
        <div class="tp-card" style="text-align:center;padding:40px 20px;color:var(--ink2);">
            <i class="fas fa-hand-pointer" style="font-size:2rem;opacity:.35;"></i>
            <p style="margin:12px 0 0;font-size:.95rem;">ยังไม่มีบันทึกการคลิกตามเงื่อนไขที่เลือก</p>
            <p class="tp-muted" style="margin:8px 0 0;font-size:.82rem;line-height:1.7;">
                ระบบจะบันทึกทุกครั้งที่ลูกค้ากดลิงก์ <code>/go/{short_code}</code> ของสมาชิกแล้วถูกส่งต่อไปลาซาด้า
                <br>ถ้ายังไม่มีเลย แปลว่ายังไม่มีใครกดลิงก์ หรือช่วงวันที่ที่เลือกแคบเกินไป
            </p>
        </div>
    @else
        <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
            <span style="font-size:.85rem;color:var(--ink2);">
                พบทั้งหมด <b class="tp-num" style="color:var(--ink);">{{ number_format($clicks->total()) }}</b> คลิก
            </span>
            <span class="tp-muted" style="font-size:.78rem;">
                หน้า {{ number_format($clicks->currentPage()) }} / {{ number_format($clicks->lastPage()) }}
            </span>
        </div>

        <div class="tp-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.83rem;min-width:940px;">
                    <thead>
                        <tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                            <th style="padding:11px 12px;font-weight:600;white-space:nowrap;">เวลา</th>
                            <th style="padding:11px 12px;font-weight:600;">สมาชิก</th>
                            <th style="padding:11px 12px;font-weight:600;">สินค้า</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:center;">ไม่ซ้ำ?</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:center;">แปลงเป็นออเดอร์?</th>
                            <th style="padding:11px 12px;font-weight:600;">ออเดอร์</th>
                            <th style="padding:11px 12px;font-weight:600;">IP</th>
                            <th style="padding:11px 12px;font-weight:600;">อ้างอิงจาก</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clicks as $click)
                            @php
                                // ── ปิดบัง IP ชุดท้าย (นโยบายความเป็นส่วนตัว) ──
                                $rawIp = trim((string) ($click->ip_address ?? ''));

                                if ($rawIp === '') {
                                    $shownIp = '—';
                                } elseif (str_contains($rawIp, ':')) {
                                    // IPv6: ตัด 2 กลุ่มท้ายทิ้ง
                                    $parts = explode(':', $rawIp);
                                    $parts = array_slice($parts, 0, max(1, count($parts) - 2));
                                    $shownIp = implode(':', $parts).':xxxx';
                                } else {
                                    $parts = explode('.', $rawIp);
                                    if (count($parts) === 4) {
                                        $parts[3] = 'xxx';
                                        $shownIp = implode('.', $parts);
                                    } else {
                                        $shownIp = 'ซ่อนไว้';
                                    }
                                }

                                // ── แหล่งอ้างอิง: โชว์แค่ชื่อโดเมน (สั้นและไม่หลุด query string) ──
                                $referer = trim((string) ($click->referer_url ?? ''));
                                $refererHost = $referer !== '' ? (parse_url($referer, PHP_URL_HOST) ?: null) : null;

                                $link = $click->affiliateLink;
                                $memberName = $link?->user?->name;
                                $productName = $link?->product?->name;
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);">
                                {{-- เวลา --}}
                                <td class="tp-num" style="padding:10px 12px;color:var(--ink);white-space:nowrap;">
                                    {{ $click->clicked_at ? $click->clicked_at->format('d/m/Y H:i:s') : '—' }}
                                </td>

                                {{-- สมาชิก --}}
                                <td style="padding:10px 12px;color:var(--ink);">
                                    @if($link)
                                        <div style="max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $memberName ?: 'สมาชิก #'.$link->user_id }}
                                        </div>
                                        <div class="tp-muted tp-num" style="font-size:.68rem;">
                                            #{{ $link->user_id }} · {{ $link->short_code }}
                                        </div>
                                    @else
                                        <span class="tp-muted">ลิงก์ถูกลบแล้ว</span>
                                    @endif
                                </td>

                                {{-- สินค้า --}}
                                <td style="padding:10px 12px;color:var(--ink);">
                                    <div style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        @if($productName)
                                            {{ $productName }}
                                        @elseif($link)
                                            <span class="tp-muted">สินค้า #{{ $link->product_id }} (ไม่พบในแคตตาล็อก)</span>
                                        @else
                                            <span class="tp-muted">—</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- ไม่ซ้ำ? --}}
                                <td style="padding:10px 12px;text-align:center;">
                                    @if($click->is_unique_click)
                                        <span class="tp-pill" style="background:var(--accent1);color:#fff;font-size:9.5px;">ไม่ซ้ำ</span>
                                    @else
                                        <span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:9.5px;">ซ้ำ</span>
                                    @endif
                                </td>

                                {{-- แปลงเป็นออเดอร์? --}}
                                <td style="padding:10px 12px;text-align:center;">
                                    @if($click->converted)
                                        <span class="tp-pill" style="background:#5aa07e;color:#fff;font-size:9.5px;">แปลงแล้ว</span>
                                    @else
                                        <span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:9.5px;">ยัง</span>
                                    @endif
                                </td>

                                {{-- ออเดอร์ --}}
                                <td style="padding:10px 12px;">
                                    @if($click->order_id)
                                        <a href="{{ route('admin.marketplace.orders.show', $click->order_id) }}"
                                           class="tp-num" style="color:var(--accent1);text-decoration:none;">
                                            #{{ $click->order_id }}
                                        </a>
                                    @else
                                        <span class="tp-muted">—</span>
                                    @endif
                                </td>

                                {{-- IP (ปิดบังชุดท้าย) --}}
                                <td class="tp-num" style="padding:10px 12px;color:var(--ink2);white-space:nowrap;">{{ $shownIp }}</td>

                                {{-- อ้างอิงจาก --}}
                                <td style="padding:10px 12px;color:var(--ink2);">
                                    <div style="max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $refererHost ?: 'เข้าตรง' }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $clicks->links() }}</div>
    @endif
</div>
@endsection
