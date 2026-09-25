@extends('layouts.seller-v4')

@section('title', 'เซสชันการขาย POS')

@php
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="เซสชันการขาย" icon="🔐" crumb="ร้านค้า · POS"
                         subtitle="ประวัติการเปิด–ปิดกะของแต่ละอุปกรณ์ พร้อมยอดเงินสดในลิ้นชัก" />

    @include('seller.pos.partials.nav')

    {{-- ตัวกรอง --}}
    <form method="GET" action="{{ route('seller.pos.sessions') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="f-device" style="font-size:12px; font-weight:700; color:var(--ink2);">อุปกรณ์</label>
            <select id="f-device" name="device_id" class="tp-input" style="margin-top:6px;">
                <option value="">ทั้งหมด</option>
                @foreach($devices as $dev)
                    <option value="{{ $dev->id }}" @selected((string) request('device_id') === (string) $dev->id)>{{ $dev->device_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-status" style="font-size:12px; font-weight:700; color:var(--ink2);">สถานะ</label>
            <select id="f-status" name="status" class="tp-input" style="margin-top:6px;">
                <option value="">ทั้งหมด</option>
                <option value="open" @selected(request('status') === 'open')>เปิดอยู่</option>
                <option value="closed" @selected(request('status') === 'closed')>ปิดแล้ว</option>
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 กรอง</button>
            <a href="{{ route('seller.pos.sessions') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($sessions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">รหัสเซสชัน</th>
                            <th style="{{ $th }}">อุปกรณ์</th>
                            <th style="{{ $th }}">พนักงาน</th>
                            <th style="{{ $th }}">เปิดกะ</th>
                            <th style="{{ $th }}">ปิดกะ</th>
                            <th style="{{ $th }} text-align:right;">ยอดขาย</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }}"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}" class="tp-num">{{ $session->session_code ?: '#'.$session->id }}</td>
                                <td style="{{ $td }}">
                                    <div style="font-weight:700;">{{ $session->posDevice->device_name ?? '-' }}</div>
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $session->posDevice->device_code ?? '' }}</div>
                                </td>
                                <td style="{{ $td }}">{{ $session->user->name ?? '-' }}</td>
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ optional($session->opened_at)->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : '—' }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:700;" class="tp-num">฿{{ number_format((float) $session->total_sales, 2) }}</td>
                                <td style="{{ $td }} text-align:center;">
                                    <x-seller-kit.pill :tone="$session->status === 'open' ? 'ok' : 'muted'">{{ $session->status === 'open' ? '● เปิดอยู่' : 'ปิดแล้ว' }}</x-seller-kit.pill>
                                </td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.pos.sessions.show', $session) }}" style="font-weight:700; color:var(--deep1); text-decoration:none;">ดูรายละเอียด →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $sessions->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="🔐" title="ไม่พบเซสชัน" text="ยังไม่มีการเปิดกะ หรือลองปรับตัวกรองใหม่" />
        @endif
    </div>
</div>
@endsection
