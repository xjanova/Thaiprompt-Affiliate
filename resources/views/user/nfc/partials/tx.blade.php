{{--
    ธุรกรรมการ์ด NFC 1 แถว (nvm-tx) — ใช้ในหน้ารวมการ์ดและหน้ารายละเอียดการ์ด
    รับ: $tx (NFCTransaction) · ชำระเงิน = เงินออก ส่วนประเภทอื่น (เติม/คืน/โอนเข้า) = เงินเข้า ตามสัญลักษณ์ของหน้าเดิม
--}}
@php
    $txOut = $tx->type === 'payment';
    $txTone = match ($tx->status) {
        'completed' => 'ok',
        'failed' => 'bad',
        'pending', 'processing' => 'warn',
        default => '',
    };
@endphp
<li class="nvm-tx">
    <span class="nvm-tx__ic {{ $txOut ? '' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $txOut ? 'up' : 'down' }}"/></svg></span>
    <div class="nvm-tx__t">
        <b>{{ $tx->type_label }}</b>
        <small class="nvm-num">#{{ $tx->transaction_id }}@if($tx->nfcReader) · {{ $tx->nfcReader->name }}@elseif($tx->location) · {{ $tx->location }}@endif</small>
    </div>
    <div class="nvm-tx__r">
        <span class="nvm-tx__amt nvm-num {{ $txOut ? '' : 'is-in' }} {{ in_array($tx->status, ['failed', 'cancelled'], true) ? 'is-void' : '' }}">{{ $txOut ? '-' : '+' }}฿{{ number_format((float) $tx->amount, 2) }}</span>
        <span class="nvm-chip {{ $txTone ? 'nvm-chip--'.$txTone : '' }}">{{ $tx->status_label }}</span>
        <time class="nvm-num" datetime="{{ $tx->created_at?->toIso8601String() }}">{{ $tx->created_at?->copy()->locale('th')->translatedFormat('j M Y · H:i') }}</time>
    </div>
</li>
