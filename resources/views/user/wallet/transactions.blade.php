@extends('layouts.user-v4')

@section('title', 'ประวัติธุรกรรม')

@php
    // ── map status_color → hex (ใช้ใน V4 แทน dynamic tailwind class) ──
    $txColor = function ($c) {
        return match ($c) {
            'green' => '#5aa07e', 'yellow', 'amber' => '#e0a52e',
            'red', 'rose' => '#d9534f', 'blue', 'indigo' => '#5689b8',
            default => 'var(--ink2)',
        };
    };
    // ── ประเภทที่นับเป็นรายรับ (แสดง + สีเขียว) ──
    $incomeTypes = ['deposit', 'transfer_in', 'commission', 'refund', 'bonus'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: หัวข้อ + ยอดคงเหลือ ─────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                    <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                @endif
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-file-invoice-dollar" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ประวัติธุรกรรม</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายการธุรกรรมทั้งหมดของกระเป๋าเงิน</div>
                </div>
            </div>

            {{-- ยอดคงเหลือ --}}
            <div style="margin-top:18px; padding:20px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินคงเหลือปัจจุบัน</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,46px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── เมนูด่วน (คงลิงก์เดิมไว้) ─────────────────────────── --}}
    @php
        $txQuick = [
            ['💵', 'ฝากเงิน', 'user.wallet.deposit'],
            ['💸', 'ถอนเงิน', 'user.wallet.withdraw'],
            ['📤', 'โอนเงิน', 'user.wallet.transfer'],
            ['📋', 'การถอน', 'user.wallet.withdrawals'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
        @foreach($txQuick as [$emoji, $label, $route])
            @if(\Illuminate\Support\Facades\Route::has($route))
                <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:7px; padding:14px 10px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:14px; font-size:20px;">{{ $emoji }}</span>
                    <span style="font-size:12.5px; font-weight:700;">{{ $label }}</span>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">🔍 ตัวกรอง</div>

        <form method="GET" action="{{ route('user.wallet.transactions') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ประเภทธุรกรรม</label>
                <select name="type" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>ฝากเงิน</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>ถอนเงิน</option>
                    <option value="transfer_in" {{ request('type') === 'transfer_in' ? 'selected' : '' }}>รับโอน</option>
                    <option value="transfer_out" {{ request('type') === 'transfer_out' ? 'selected' : '' }}>โอนออก</option>
                    <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>คอมมิชชั่น</option>
                    <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเงิน</option>
                    <option value="fee" {{ request('type') === 'fee' ? 'selected' : '' }}>ค่าธรรมเนียม</option>
                    <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>โบนัส</option>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">วันที่เริ่มต้น</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input">
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input">
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 กรอง</button>
                <a href="{{ route('user.wallet.transactions') }}" class="tp-btn">ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ── ตารางรายการธุรกรรม ──────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div>
                <div class="tp-section-h">📋 รายการธุรกรรม</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">ทั้งหมด {{ $transactions->total() }} รายการ</div>
            </div>
            <button type="button" onclick="window.print()" class="tp-btn tp-btn-sm no-print">🖨️ พิมพ์</button>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">รหัสธุรกรรม</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">ประเภท</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">รายละเอียด</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">จำนวนเงิน</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">ยอดคงเหลือ</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:center;">สถานะ</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                            <td class="tp-num" style="padding:12px 16px; white-space:nowrap; font-size:11px; color:var(--ink2);">{{ $transaction->transaction_id }}</td>
                            <td style="padding:12px 16px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:20px;">{{ $transaction->type_icon }}</span>
                                    <span style="font-size:12.5px; font-weight:600;">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td style="padding:12px 16px;">
                                <div style="font-size:12.5px; font-weight:600;">{{ $transaction->description }}</div>
                                @if($transaction->relatedWallet)
                                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">
                                        @if($transaction->type === 'transfer_in')
                                            จาก: {{ $transaction->relatedWallet->user->name ?? '—' }}
                                        @elseif($transaction->type === 'transfer_out')
                                            ถึง: {{ $transaction->relatedWallet->user->name ?? '—' }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:{{ in_array($transaction->type, $incomeTypes) ? '#5aa07e' : '#d9534f' }};">
                                {{ in_array($transaction->type, $incomeTypes) ? '+' : '-' }}฿{{ number_format(abs($transaction->amount), 2) }}
                            </td>
                            <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                <div style="font-weight:600;">฿{{ number_format($transaction->balance_after, 2) }}</div>
                                <div style="font-size:11px; color:var(--ink2);">จาก ฿{{ number_format($transaction->balance_before, 2) }}</div>
                            </td>
                            <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                <span class="tp-pill" style="color:{{ $txColor($transaction->status_color) }}; background:color-mix(in srgb, {{ $txColor($transaction->status_color) }} 16%, transparent);">{{ $transaction->status_label }}</span>
                            </td>
                            <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11px; color:var(--ink2);">{{ $transaction->created_at->format('H:i:s') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 20px; text-align:center; color:var(--ink2);">
                                <div style="font-size:46px; opacity:.5;">📭</div>
                                <div style="margin-top:8px; font-weight:600;">ไม่พบรายการธุรกรรม</div>
                                @if(request()->hasAny(['type', 'date_from', 'date_to']))
                                    <a href="{{ route('user.wallet.transactions') }}" class="tp-btn tp-btn-sm" style="margin-top:12px;">ล้างตัวกรอง</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ── สรุปสถิติ 3 ใบ ───────────────────────────────────── --}}
    @php
        $sumStats = [
            ['รายรับทั้งหมด', '฿' . number_format($wallet->total_income, 2), '📥', '#5aa07e'],
            ['รายจ่ายทั้งหมด', '฿' . number_format($wallet->total_expense, 2), '📤', '#d9534f'],
            ['ยอดคงเหลือ', '฿' . number_format($wallet->balance, 2), '💰', '#5689b8'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @foreach($sumStats as [$label, $val, $emoji, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:18px; background:color-mix(in srgb, {{ $color }} 18%, transparent);">{{ $emoji }}</span>
                </div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:10px; color:{{ $color }};">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    @php
        $quickActions = [
            ['💵', 'ฝากเงิน', 'user.wallet.deposit'],
            ['💸', 'ถอนเงิน', 'user.wallet.withdraw'],
            ['📤', 'โอนเงิน', 'user.wallet.transfer'],
            ['📋', 'ประวัติถอน', 'user.wallet.withdrawals'],
        ];
    @endphp
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">⚡ การกระทำด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
            @foreach($quickActions as [$emoji, $label, $route])
                @if(\Illuminate\Support\Facades\Route::has($route))
                    <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px; text-decoration:none; color:inherit;">
                        <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:21px;">{{ $emoji }}</span>
                        <span style="font-size:13px; font-weight:700; text-align:center;">{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<style>
@media print {
    .no-print { display: none !important; }
}
</style>
@endpush
@endsection
