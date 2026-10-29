{{--
 | แถวธุรกรรมหนึ่งรายการ (ธีมโนวา) — ใช้ในหน้ากระเป๋าเงิน + ประวัติธุรกรรม
 | รับ $tx (WalletTransaction, ควร eager load relatedWallet.user มาด้วย) · $showBalance=true → โชว์ยอดก่อน/หลังรายการ
 | เครื่องหมาย +/- อ่านจาก formatted_amount ของโมเดล (รายการรับเงินชุดเดียวกับโมเดล รวม cashback)
 --}}
@php
    $txIn = str_starts_with((string) $tx->formatted_amount, '+');
    $txIcon = match ($tx->type) {
        'deposit', 'transfer_in' => 'down',
        'withdrawal' => 'cash',
        'transfer_out' => 'send',
        'commission' => 'award',
        'refund' => 'history',
        'fee' => 'card',
        'bonus', 'cashback' => 'gift',
        default => 'swap',
    };
    $txTone = match ($tx->status) {
        'completed' => 'nvm-chip--ok',
        'processing' => 'nvm-chip--warn',
        'pending' => 'nvm-chip--info',
        'failed' => 'nvm-chip--bad',
        default => '',
    };
    $txWho = $tx->relatedWallet
        ? ($tx->type === 'transfer_in' ? 'จาก ' : 'ถึง ').($tx->relatedWallet->user->name ?? '—')
        : null;
@endphp
<li class="nvm-tx">
    <span class="nvm-tx__ic {{ $txIn ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $txIcon }}"/></svg></span>
    <div class="nvm-tx__t">
        <b>{{ $tx->description ?: $tx->type_label }}</b>
        <small>{{ $tx->type_label }}@if($txWho) · {{ $txWho }}@endif</small>
        <small class="nvm-num">ID: {{ $tx->transaction_id }}</small>
        @if(! empty($showBalance))
            <small class="nvm-num">คงเหลือ ฿{{ number_format((float) $tx->balance_after, 2) }} · ก่อนหน้า ฿{{ number_format((float) $tx->balance_before, 2) }}</small>
        @endif
    </div>
    <div class="nvm-tx__r">
        <span class="nvm-tx__amt nvm-num {{ in_array($tx->status, ['failed', 'cancelled'], true) ? 'is-void' : ($txIn ? 'is-in' : '') }}">{{ $tx->formatted_amount }}</span>
        <span class="nvm-chip {{ $txTone }}">{{ $tx->status_label }}</span>
        @if($tx->created_at)
            <time class="nvm-num" datetime="{{ $tx->created_at->toIso8601String() }}">{{ $tx->created_at->format('d/m/Y H:i') }}</time>
        @endif
    </div>
</li>
