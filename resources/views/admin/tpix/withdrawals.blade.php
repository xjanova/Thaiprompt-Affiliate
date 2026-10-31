@extends('layouts.admin-v4')

@section('title', 'คิวถอน TPIX')

@section('content')
{{-- ═══════════════════════════════════════════════════════
     หน้า: คิวถอน TPIX (ธีม V4)
     ข้อมูลจาก Admin\TpixWithdrawalAdminController@index: $pending, $history (paginate), $stats
     ═══════════════════════════════════════════════════════ --}}
@php
    $money = fn ($value) => rtrim(rtrim((string) $value, '0'), '.') ?: '0';
    $refund = fn ($withdrawal) => $money(bcadd((string) $withdrawal->amount, (string) $withdrawal->fee, 18));
    $oldFor = fn ($field, $id) => (int) session('tpix_withdrawal_row') === (int) $id ? old($field) : '';
    $statusBadge = [
        'confirmed' => ['#2f855a', 'ส่งแล้ว'],
        'completed' => ['#2f855a', 'ส่งแล้ว'],
        'cancelled' => ['#718096', 'ปฏิเสธ คืนยอดแล้ว'],
        'failed' => ['#c53030', 'ไม่สำเร็จ'],
        'broadcasted' => ['#2b6cb0', 'กำลังส่ง'],
        'confirming' => ['#2b6cb0', 'กำลังส่ง'],
    ];
@endphp
<div style="max-width:1180px;margin:0 auto;display:flex;flex-direction:column;gap:18px;">

    <div>
        <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">หลังบ้าน · การเงิน</div>
        <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">🪙 คิวถอน TPIX</h1>
    </div>

    {{-- ── วิธีใช้: ต้องเห็นก่อนกดทุกครั้ง ── --}}
    <div class="tp-card" style="border-left:4px solid #b7791f;">
        <div style="font-weight:800;color:var(--ink);margin-bottom:4px;">หน้านี้ไม่ได้ส่งเหรียญให้</div>
        <div class="tp-muted" style="font-size:.86rem;line-height:1.6;">
            โอนเหรียญจากกระเป๋าของระบบเองก่อน แล้วค่อยกรอก tx hash ที่นี่
            ระบบตรวจ hash บนเชนไม่ได้ จึงต้องเช็คยอดและที่อยู่ปลายทางให้ตรงก่อนบันทึก
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;">
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">รอดำเนินการ</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ number_format($stats['pending_count']) }} รายการ</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">ยอดที่ต้องส่ง</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);word-break:break-all;">{{ $money($stats['pending_amount']) }} TPIX</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">ส่งแล้ว</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ number_format($stats['confirmed_count']) }} รายการ</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">ปฏิเสธและคืนยอด</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ number_format($stats['cancelled_count']) }} รายการ</div>
        </div>
    </div>

    {{-- ── คิวรอดำเนินการ เก่าสุดก่อน ── --}}
    <div style="display:flex;flex-direction:column;gap:12px;">
        <h2 style="font-size:1.05rem;font-weight:800;color:var(--ink);margin:0;">รอดำเนินการ</h2>

        @forelse($pending as $withdrawal)
            <div class="tp-card" style="display:flex;flex-direction:column;gap:14px;" data-testid="pending-{{ $withdrawal->id }}">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;">
                    <div style="min-width:0;">
                        <div style="font-weight:700;color:var(--ink);overflow-wrap:anywhere;">{{ $withdrawal->user?->name ?? 'ไม่พบผู้ใช้' }}</div>
                        <div class="tp-muted" style="font-size:.8rem;overflow-wrap:anywhere;">{{ $withdrawal->user?->email }} · ขอเมื่อ {{ $withdrawal->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="tp-muted" style="font-size:.75rem;">ยอดที่ต้องส่ง</div>
                        <div class="tp-num" style="font-size:1.2rem;font-weight:800;color:var(--ink);">{{ $money($withdrawal->amount) }} TPIX</div>
                        <div class="tp-muted" style="font-size:.75rem;">ค่าธรรมเนียม {{ $money($withdrawal->fee) }} · ถ้าปฏิเสธคืน {{ $refund($withdrawal) }}</div>
                    </div>
                </div>

                <div>
                    <div class="tp-muted" style="font-size:.75rem;margin-bottom:2px;">ที่อยู่ปลายทาง</div>
                    <div style="font-family:monospace;font-size:.86rem;color:var(--ink);overflow-wrap:anywhere;user-select:all;">{{ $withdrawal->to_address }}</div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
                    <form method="POST" action="{{ route('admin.tpix.withdrawals.confirm', $withdrawal->id) }}"
                          x-data="{ busy: false }"
                          x-on:submit="if (busy || !confirm(@js('ยืนยันว่าส่ง '.$money($withdrawal->amount).' TPIX ไปที่ '.$withdrawal->to_address.' แล้ว?'))) { $event.preventDefault(); return; } busy = true"
                          style="display:flex;flex-direction:column;gap:8px;">
                        @csrf
                        <label class="tp-muted" for="tx-hash-{{ $withdrawal->id }}" style="font-size:.8rem;">tx hash หลังโอนแล้ว</label>
                        <input id="tx-hash-{{ $withdrawal->id }}" name="tx_hash" class="tp-input" required autocomplete="off" spellcheck="false"
                               pattern="0x[a-fA-F0-9]{64}" title="0x ตามด้วยตัวอักษรฐานสิบหก 64 ตัว" placeholder="0x..." value="{{ $oldFor('tx_hash', $withdrawal->id) }}">
                        <button type="submit" class="tp-btn tp-btn-primary" style="min-height:44px;" x-bind:disabled="busy">ยืนยันว่าส่งแล้ว</button>
                    </form>

                    <form method="POST" action="{{ route('admin.tpix.withdrawals.reject', $withdrawal->id) }}"
                          x-data="{ busy: false }"
                          x-on:submit="if (busy || !confirm(@js('ปฏิเสธรายการนี้และคืน '.$refund($withdrawal).' TPIX เข้ากระเป๋าสมาชิก?'))) { $event.preventDefault(); return; } busy = true"
                          style="display:flex;flex-direction:column;gap:8px;">
                        @csrf
                        <label class="tp-muted" for="reason-{{ $withdrawal->id }}" style="font-size:.8rem;">เหตุผลที่ปฏิเสธ (สมาชิกจะเห็น)</label>
                        <input id="reason-{{ $withdrawal->id }}" name="reason" class="tp-input" required maxlength="500" autocomplete="off" value="{{ $oldFor('reason', $withdrawal->id) }}">
                        <button type="submit" class="tp-btn" style="min-height:44px;" x-bind:disabled="busy">ปฏิเสธและคืนยอด</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="tp-card tp-muted" style="text-align:center;padding:32px;">ไม่มีรายการรอดำเนินการ</div>
        @endforelse

        <div>{{ $pending->links() }}</div>
    </div>

    {{-- ── ประวัติ ── --}}
    <div style="display:flex;flex-direction:column;gap:12px;">
        <h2 style="font-size:1.05rem;font-weight:800;color:var(--ink);margin:0;">ประวัติ</h2>

        @forelse($history as $withdrawal)
            @php [$badgeColor, $badgeLabel] = $statusBadge[$withdrawal->status] ?? ['#718096', $withdrawal->status]; @endphp
            <div class="tp-card" style="display:flex;flex-direction:column;gap:6px;">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center;">
                    <div style="min-width:0;font-weight:700;color:var(--ink);overflow-wrap:anywhere;">
                        <span class="tp-num">{{ $money($withdrawal->amount) }} TPIX</span> · {{ $withdrawal->user?->name ?? 'ไม่พบผู้ใช้' }}
                    </div>
                    <span class="tp-pill" style="background:{{ $badgeColor }};color:#fff;">{{ $badgeLabel }}</span>
                </div>
                <div class="tp-muted" style="font-size:.8rem;overflow-wrap:anywhere;">ปลายทาง <span style="font-family:monospace;">{{ $withdrawal->to_address }}</span></div>
                @if($withdrawal->tx_hash)
                    <div class="tp-muted" style="font-size:.8rem;overflow-wrap:anywhere;">tx hash <span style="font-family:monospace;">{{ $withdrawal->tx_hash }}</span></div>
                @endif
                @if($withdrawal->status === 'cancelled' && $withdrawal->error_message)
                    <div class="tp-muted" style="font-size:.8rem;overflow-wrap:anywhere;">เหตุผล: {{ $withdrawal->error_message }}</div>
                @endif
                <div class="tp-muted" style="font-size:.75rem;">
                    {{ $withdrawal->reviewed_at?->format('d/m/Y H:i') ?? '—' }} · โดย {{ $withdrawal->reviewer?->name ?? 'ระบบ' }}
                </div>
            </div>
        @empty
            <div class="tp-card tp-muted" style="text-align:center;padding:32px;">ยังไม่มีประวัติ</div>
        @endforelse

        <div>{{ $history->links() }}</div>
    </div>
</div>
@endsection
