{{--
    แถวธุรกรรมคริปโต 1 รายการ (nvm-tx) — แตะเพื่อดูรายละเอียด
    $tx = CryptoTransaction ของสมาชิกคนนี้ (โหลด currency มาแล้ว)
--}}
@php
    $cwTxCode = $tx->currency?->code ?? '-';
    $cwTxIn = $tx->is_incoming;
@endphp
<li class="nvm-tx">
    <span class="nvm-tx__ic {{ $cwTxIn ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $cwTxIn ? 'down' : 'up' }}"/></svg></span>
    <div class="nvm-tx__t">
        <b>{{ $tx->typeLabel() }} {{ $cwTxCode }}</b>
        <small><a class="nvm-link" href="{{ route('user.crypto-wallet.transaction.detail', $tx->id) }}">ดูรายละเอียด</a> · {{ $tx->created_at?->locale('th')->diffForHumans() }}</small>
    </div>
    <div class="nvm-tx__r">
        <span class="nvm-tx__amt nvm-num {{ $cwTxIn ? 'is-in' : '' }}">{{ $cwTxIn ? '+' : '-' }}{{ number_format((float) $tx->amount, 8) }}</span>
        @include('user.crypto-wallet.partials.status', ['status' => $tx->status])
    </div>
</li>
