@extends('layouts.user-v4')

@section('title', 'ประวัติ PV (Point Value)')

@php
    // ── สรุป PV 4 ใบ (คำนวณจาก collection หน้าปัจจุบัน เหมือนเดิม) ─────────
    $pvReceived = $transactions->where('transaction_type', 'credit')->sum('pv_amount');
    $pvUsed     = $transactions->where('transaction_type', 'debit')->sum('pv_amount');
    $pvThisMonth = $transactions->filter(fn($t) => $t->created_at->isCurrentMonth())->sum('pv_amount');

    $pvCards = [
        ['label' => 'PV รวม',     'en' => 'Total PV',  'val' => $member->total_pv ?? 0, 'sign' => '',  'color' => 'var(--deep1)', 'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '💎'],
        ['label' => 'PV ได้รับ',  'en' => 'Credited',  'val' => $pvReceived,            'sign' => '+', 'color' => '#5aa07e',      'soft' => 'rgba(90,160,126,.16)', 'icon' => '➕'],
        ['label' => 'PV ใช้ไป',   'en' => 'Debited',   'val' => $pvUsed,                'sign' => '-', 'color' => '#d9534f',      'soft' => 'rgba(217,83,79,.16)',  'icon' => '➖'],
        ['label' => 'PV เดือนนี้', 'en' => 'This Month','val' => $pvThisMonth,           'sign' => '',  'color' => '#5689b8',      'soft' => 'rgba(86,137,184,.16)', 'icon' => '📅'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.dashboard'))
                <a href="{{ route('user.mlm.dashboard') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-history" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ประวัติ PV (Point Value)</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ประวัติการสะสมและใช้คะแนน</div>
            </div>
        </div>
    </div>

    {{-- ── สรุป PV 4 ใบ ─────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($pvCards as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $c['en'] }}</div>
                    </div>
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">{{ $c['sign'] }}{{ number_format($c['val'], 0) }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── ตัวกรอง ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ประเภท</label>
                <select name="type" class="tp-input" style="width:100%;">
                    <option value="">ทั้งหมด</option>
                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>รับ PV</option>
                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>ใช้ PV</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">
                    <i class="fas fa-search" style="font-size:12px;"></i> ค้นหา
                </button>
                <a href="{{ url()->current() }}" class="tp-btn">ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ── ตารางประวัติการทำรายการ ──────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; padding:16px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <span>📋</span> ประวัติการทำรายการ
        </div>

        @if($transactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['วันที่','ประเภท','รายละเอียด','PV','หมายเหตุ'] as $h)
                                <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php $isCredit = $transaction->transaction_type === 'credit'; @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                {{-- วันที่ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div class="tp-num" style="font-weight:600; color:var(--ink);">{{ $transaction->created_at->format('d/m/Y') }}</div>
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $transaction->created_at->format('H:i') }}</div>
                                </td>
                                {{-- ประเภท --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($isCredit)
                                        <span class="tp-pill" style="color:#fff; background:#5aa07e;">➕ รับ PV</span>
                                    @else
                                        <span class="tp-pill" style="color:#fff; background:#d9534f;">➖ ใช้ PV</span>
                                    @endif
                                </td>
                                {{-- รายละเอียด --}}
                                <td style="padding:12px 16px;">
                                    @if($transaction->order)
                                        <div style="font-weight:600; color:var(--ink);">คำสั่งซื้อ #{{ $transaction->order->id }}</div>
                                        <div class="tp-num" style="font-size:11px; color:var(--ink2);">
                                            {{ $transaction->order->total_amount ? '฿' . number_format($transaction->order->total_amount, 2) : '' }}
                                        </div>
                                    @elseif($transaction->product)
                                        <div style="font-weight:600; color:var(--ink);">{{ $transaction->product->name ?? 'สินค้า' }}</div>
                                    @else
                                        <span style="color:var(--ink2);">{{ $transaction->description ?? '-' }}</span>
                                    @endif
                                </td>
                                {{-- PV --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div class="tp-num" style="font-size:17px; font-weight:800; color:{{ $isCredit ? '#5aa07e' : '#d9534f' }};">
                                        {{ $isCredit ? '+' : '-' }}{{ number_format($transaction->pv_amount, 0) }}
                                    </div>
                                </td>
                                {{-- หมายเหตุ --}}
                                <td style="padding:12px 16px; color:var(--ink2); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    {{ $transaction->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
                <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    {{ $transactions->links() }}
                </div>
            @endif
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">⭐</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีประวัติการทำรายการ</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เมื่อมีการสะสมหรือใช้ PV จะแสดงที่นี่</div>
            </div>
        @endif
    </div>

    {{-- ── เกี่ยวกับ PV ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 12%, transparent), transparent 70%);">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <span>💡</span> เกี่ยวกับ PV (Point Value)
        </div>
        <div style="display:flex; flex-direction:column; gap:8px; font-size:13px; color:var(--ink2);">
            <p style="margin:0;">• <strong style="color:var(--ink);">PV</strong> คือคะแนนที่ได้จากการซื้อสินค้าหรือบริการ</p>
            <p style="margin:0;">• PV ใช้สำหรับคำนวณค่าคอมมิชชั่นและการจัดอันดับ</p>
            <p style="margin:0;">• PV สะสมจะช่วยให้คุณมีสิทธิ์รับโบนัสและสิทธิพิเศษต่างๆ</p>
            <p style="margin:0;">• ตรวจสอบเงื่อนไขการใช้ PV กับทีมงานของเรา</p>
        </div>
    </div>
</div>
@endsection
