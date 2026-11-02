{{-- การ์ดเหรียญหนึ่งใบในตลาด — รับ $token, $featured --}}
<a class="nvm-card nvm-tx-tok" href="{{ route('user.tokens.show', $token->id) }}">
    <div class="nvm-tx-head">
        @include('user.tokens.partials.coin', ['token' => $token])
        <div>
            <b>{{ $token->name }}@if($token->is_verified)<svg class="nvm-ico" aria-label="ตรวจสอบแล้ว"><use href="#nvm-i-check"/></svg>@endif</b>
            <small>{{ $token->symbol }}</small>
        </div>
    </div>
    @if($featured)
        <div><span class="nvm-chip nvm-chip--gold">แนะนำ</span></div>
    @endif
    <dl class="nvm-kv">
        <dt>ราคา</dt><dd class="nvm-num">{{ number_format((float) ($token->current_price_tpix ?? 0), 4) }} TPIX</dd>
        <dt>มูลค่าตลาด</dt><dd class="nvm-num">{{ number_format((float) ($token->market_cap_tpix ?? 0), 0) }}</dd>
        <dt>ผู้ถือ</dt><dd class="nvm-num">{{ number_format((int) ($token->holders_count ?? 0)) }}</dd>
    </dl>
    <span class="nvm-btn nvm-btn--line nvm-btn--sm">ดูรายละเอียด</span>
</a>
