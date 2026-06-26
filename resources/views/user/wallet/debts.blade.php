@extends('layouts.user-v4')

@section('title', 'หนี้ค้างชำระ')

@php
    $debtMeta = [
        'active'    => ['ค้างชำระ', '#d9534f', 'fa-triangle-exclamation'],
        'paid'      => ['ชำระแล้ว', '#5aa07e', 'fa-circle-check'],
        'waived'    => ['ยกเว้น',   '#e0a52e', 'fa-hand-holding-heart'],
        'cancelled' => ['ยกเลิก',   'var(--ink2)', 'fa-circle-xmark'],
    ];
    $totalOriginal = ($debtSummary['total_debt'] ?? 0) + ($debtSummary['total_paid'] ?? 0);
    $paidPercent = $totalOriginal > 0 ? round((($debtSummary['total_paid'] ?? 0) / $totalOriginal) * 100, 1) : 100;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ──────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 72%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-file-invoice-dollar" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">หนี้ค้างชำระ</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายการหนี้และประวัติการชำระ</div>
                </div>
            </div>
            <div style="margin-top:18px; padding:20px 22px; border-radius:18px; box-shadow:var(--inset);">
                @if($debtSummary['has_active_debt'])
                    <div style="font-size:12.5px; color:var(--ink2);">ยอดหนี้คงเหลือ</div>
                    <div class="tp-num" style="font-size:clamp(32px,7vw,48px); font-weight:800; line-height:1.1; margin-top:4px; color:#d9534f;">฿{{ number_format($debtSummary['total_debt'], 2) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">{{ $debtSummary['debt_count'] }} รายการค้างชำระ</div>
                @else
                    <div style="font-size:12.5px; color:var(--ink2);">สถานะ</div>
                    <div style="font-size:clamp(22px,5vw,30px); font-weight:800; margin-top:4px; color:#5aa07e;">✅ ไม่มีหนี้ค้างชำระ</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    @php
        $dStats = [
            ['ยอดหนี้คงเหลือ', '฿'.number_format($debtSummary['total_debt'] ?? 0, 2), '💰', '#d9534f'],
            ['จำนวนหนี้ค้าง', number_format($debtSummary['debt_count'] ?? 0).' รายการ', '📋', '#e0a52e'],
            ['ชำระแล้ว', '฿'.number_format($debtSummary['total_paid'] ?? 0, 2), '✅', '#5aa07e'],
            ['อัตราชำระ', $paidPercent.'%', '📊', '#5689b8'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
        @foreach($dStats as [$label, $val, $emoji, $color])
            <div class="tp-card" style="padding:16px; display:flex; align-items:center; gap:11px;">
                <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:19px; background:color-mix(in srgb, {{ $color }} 18%, transparent);">{{ $emoji }}</span>
                <div style="min-width:0;">
                    <div style="font-size:11.5px; color:var(--ink2);">{{ $label }}</div>
                    <div class="tp-num" style="font-size:19px; font-weight:800; color:{{ $color }};">{{ $val }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── แจ้งเตือนระบบหักหนี้อัตโนมัติ ─────────────────────── --}}
    @if($debtSummary['has_active_debt'])
    <div class="tp-card" style="padding:16px 18px; display:flex; gap:13px; align-items:flex-start; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#e0a52e;"><i class="fas fa-circle-info" style="color:#fff;"></i></span>
        <div>
            <div style="font-weight:700; font-size:14px;">ระบบหักหนี้อัตโนมัติ</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">หนี้จะถูกหักอัตโนมัติ <strong>สูงสุด 50%</strong> จากรายได้ใหม่ทุกครั้งที่มีรายรับเข้า Wallet จนกว่าจะชำระครบ ไม่ต้องดำเนินการเพิ่มเติม</div>
        </div>
    </div>
    @endif

    {{-- ── ตัวกรอง ──────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('user.wallet.debts') }}" class="tp-card" style="padding:14px 16px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
        <label style="font-size:12.5px; font-weight:600; color:var(--ink2);">กรองสถานะ:</label>
        <select name="status" onchange="this.form.submit()" class="tp-input" style="width:auto; min-width:160px;">
            <option value="">ทั้งหมด</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ค้างชำระ</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
            <option value="waived" {{ request('status') === 'waived' ? 'selected' : '' }}>ยกเว้น</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
        </select>
        @if(request('status'))
            <a href="{{ route('user.wallet.debts') }}" class="tp-btn tp-btn-sm"><i class="fas fa-xmark"></i> ล้างตัวกรอง</a>
        @endif
    </form>

    {{-- ── รายการหนี้ ───────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:12px;" x-data="{ expanded: null }">
        @forelse($debts as $debt)
            @php
                $m = $debtMeta[$debt->status] ?? [$debt->status_label ?? $debt->status, 'var(--ink2)', 'fa-circle-info'];
                [$stLabel, $stColor, $stIcon] = $m;
            @endphp
            <div class="tp-card" style="padding:0; overflow:hidden;">
                {{-- หัวการ์ด (กดเพื่อขยาย) --}}
                <div style="padding:16px 18px; cursor:pointer;" @click="expanded === {{ $debt->id }} ? expanded = null : expanded = {{ $debt->id }}">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                        <div style="display:flex; align-items:flex-start; gap:12px; min-width:0;">
                            <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:18px; background:color-mix(in srgb, {{ $stColor }} 18%, transparent);"><i class="fas {{ $stIcon }}" style="color:{{ $stColor }};"></i></span>
                            <div style="min-width:0;">
                                <div style="font-size:13.5px; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $debt->reason ?? 'หนี้จากระบบ' }}</div>
                                <div class="tp-num" style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $debt->source_type }} #{{ $debt->source_id }} · {{ $debt->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="text-align:right;">
                                <div class="tp-num" style="font-size:17px; font-weight:800; color:#d9534f;">฿{{ number_format($debt->original_amount, 2) }}</div>
                                @if($debt->status === 'active' && $debt->deducted_amount > 0)
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">คงเหลือ ฿{{ number_format($debt->remaining_amount, 2) }}</div>
                                @endif
                            </div>
                            <span class="tp-pill" style="color:#fff; background:{{ $stColor }};">{{ $debt->status_label ?? $stLabel }}</span>
                            <i class="fas fa-chevron-down" style="color:var(--ink2); transition:transform .2s ease;" :style="{ transform: expanded === {{ $debt->id }} ? 'rotate(180deg)' : 'none' }"></i>
                        </div>
                    </div>

                    {{-- progress (active) --}}
                    @if($debt->status === 'active' && $debt->original_amount > 0)
                    <div style="margin-top:14px;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--ink2); margin-bottom:5px;">
                            <span>ชำระแล้ว {{ number_format($debt->paid_percentage, 1) }}%</span>
                            <span class="tp-num">฿{{ number_format($debt->deducted_amount, 2) }} / ฿{{ number_format($debt->original_amount, 2) }}</span>
                        </div>
                        <div style="height:10px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                            <div style="height:100%; width:{{ min($debt->paid_percentage, 100) }}%; border-radius:20px; background:linear-gradient(90deg,#e0a52e,#d9534f);"></div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- รายละเอียด (ขยาย) --}}
                <div x-show="expanded === {{ $debt->id }}" x-collapse x-cloak
                     style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); padding:16px 18px; box-shadow:var(--inset-sm);">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-bottom:12px;">
                        <div><div style="font-size:11px; color:var(--ink2);">ยอดเดิม</div><div class="tp-num" style="font-weight:700;">฿{{ number_format($debt->original_amount, 2) }}</div></div>
                        <div><div style="font-size:11px; color:var(--ink2);">หักแล้ว</div><div class="tp-num" style="font-weight:700; color:#5aa07e;">฿{{ number_format($debt->deducted_amount, 2) }}</div></div>
                        <div><div style="font-size:11px; color:var(--ink2);">คงเหลือ</div><div class="tp-num" style="font-weight:700; color:#d9534f;">฿{{ number_format($debt->remaining_amount, 2) }}</div></div>
                        <div><div style="font-size:11px; color:var(--ink2);">ลำดับความสำคัญ</div><div class="tp-num" style="font-weight:700;">{{ $debt->priority }}</div></div>
                    </div>
                    @if($debt->fully_paid_at)
                        <div style="font-size:12px; color:#5aa07e; margin-bottom:4px;"><i class="fas fa-circle-check"></i> ชำระครบวันที่ {{ $debt->fully_paid_at->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($debt->waived_at)
                        <div style="font-size:12px; color:#e0a52e; margin-bottom:4px;"><i class="fas fa-hand-holding-heart"></i> ยกเว้นวันที่ {{ $debt->waived_at->format('d/m/Y H:i') }}@if($debt->waive_reason) — {{ $debt->waive_reason }}@endif</div>
                    @endif
                    @php $history = $debt->metadata['deduction_history'] ?? []; @endphp
                    @if(!empty($history))
                        <div style="margin-top:10px;">
                            <div style="font-size:12.5px; font-weight:700; margin-bottom:7px;"><i class="fas fa-clock-rotate-left"></i> ประวัติการหัก</div>
                            <div style="display:flex; flex-direction:column; gap:6px; max-height:200px; overflow-y:auto;">
                                @foreach(array_reverse($history) as $entry)
                                    <div style="display:flex; align-items:center; justify-content:space-between; padding:7px 11px; border-radius:10px; box-shadow:var(--inset-sm); font-size:11.5px;">
                                        <span class="tp-num" style="color:var(--ink2);">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y H:i') }}</span>
                                        <span class="tp-num" style="font-weight:700; color:#5aa07e;">-฿{{ number_format($entry['amount'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="tp-card" style="text-align:center; padding:48px 20px;">
                <div style="font-size:52px;">✅</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ไม่มีหนี้ค้างชำระ</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">คุณไม่มีรายการหนี้{{ request('status') ? 'ในสถานะที่เลือก' : '' }}</div>
            </div>
        @endforelse
    </div>

    {{-- ── Pagination ───────────────────────────────────────── --}}
    @if($debts->hasPages())
        <div class="tp-card" style="padding:14px 16px;">{{ $debts->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
