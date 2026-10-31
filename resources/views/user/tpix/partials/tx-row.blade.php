{{-- หนึ่งแถวรายการ TPIX ลิงก์ไปหน้ารายละเอียด — รับ $transaction --}}
@php
    $incoming = $transaction->is_incoming;
@endphp
<a href="{{ route('user.tpix.transactions.details', $transaction->id) }}" style="padding:14px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 9%, transparent); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px 14px; text-decoration:none;">
    <div style="min-width:0;">
        <div class="tp-num" style="font-weight:700; color:{{ $incoming ? '#5aa07e' : 'var(--ink)' }}; font-size:14px; overflow-wrap:anywhere;">{{ $incoming ? '+' : '-' }}{{ number_format((float) $transaction->amount, 8) }} TPIX</div>
        <div style="font-size:12px; color:var(--ink2); margin-top:2px; overflow-wrap:anywhere;">
            {{ $transaction->typeLabel() }} · {{ $transaction->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
    @include('user.tpix.partials.status-pill', ['status' => $transaction->status])
</a>
