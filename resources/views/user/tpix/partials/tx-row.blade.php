{{-- หนึ่งแถวรายการ TPIX ลิงก์ไปหน้ารายละเอียด (ธีมโนวา) — รับ $transaction · วางใน <div class="nvm-txs nvm-tks"> --}}
@php
    $incoming = $transaction->is_incoming;
@endphp
<a class="nvm-tx" href="{{ route('user.tpix.transactions.details', $transaction->id) }}">
    <span class="nvm-tx__ic {{ $incoming ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $incoming ? 'down' : 'up' }}"/></svg></span>
    <span class="nvm-tx__t">
        <b>{{ $transaction->typeLabel() }}</b>
        <small class="nvm-num">{{ $transaction->created_at->format('d/m/Y H:i') }}</small>
    </span>
    <span class="nvm-tx__r">
        <span class="nvm-tx__amt nvm-num {{ $incoming ? 'is-in' : '' }}">{{ $incoming ? '+' : '-' }}{{ number_format((float) $transaction->amount, 8) }}</span>
        @include('user.tpix.partials.status-pill', ['status' => $transaction->status])
    </span>
</a>
