{{--
    การ์ด NFC Tap-to-Pay ของฉัน (ธีมโนวาเต็ม) — NFCCardController::index
    $cards = การ์ดของสมาชิกเอง (พร้อม wallet + ธุรกรรมล่าสุด 5 รายการ) · $wallet = กระเป๋าเงินของสมาชิก
    - เติมเงินส่ง POST user.nfc.topup (amount + pin 6 หลัก) · route นี้มีด่าน two-factor:nfc_topup
    - ปิดการใช้งานการ์ดต้องยืนยันก่อน (ปิดแล้วแตะจ่ายไม่ได้)
    - ชื่อการ์ดส่งเข้า Alpine ผ่าน @js — เดิมแปะลง onclick ตรง ๆ ชื่อที่มี ' ทำให้ปุ่มเติมเงินพัง
--}}
@extends('layouts.user-nova')

@section('title', 'การ์ด NFC Tap-to-Pay')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $chip = fn (string $color) => match ($color) {
        'green' => 'ok',
        'red' => 'bad',
        'orange', 'yellow' => 'warn',
        'blue' => 'info',
        default => '',
    };
    $kpis = [
        ['ยอดเงินในการ์ดรวม', 'Card balance', $baht($totalBalance), 'images/nova/wallet/card.webp', 'จากการ์ดทั้งหมด '.number_format($totalCards).' ใบ'],
        ['การ์ดที่ใช้งานได้', 'Active cards', number_format($activeCards).' / '.number_format($totalCards), 'images/nova/deco/coins.webp', 'เปิดใช้งานและสถานะปกติ'],
        ['กระเป๋าเงินของคุณ', 'Wallet', $baht($wallet->balance ?? 0), 'images/nova/wallet/deposit.webp', 'พร้อมเติมเข้าการ์ด'],
    ];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmNfcCards()">

<section class="nvm-ph" aria-labelledby="nvm-nfc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Tap to pay</div>
            <h1 id="nvm-nfc-h">การ์ด NFC ของฉัน</h1>
            <p class="nvm-ph__lead">แตะจ่ายที่จุดรับชำระ เติมเงินจากกระเป๋า ตั้งวงเงินต่อวัน/เดือน และดูประวัติการใช้งานได้ที่นี่</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis nvm-kpis--3" aria-label="สรุปการ์ด NFC">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

@forelse($cards as $card)
    @php
        $enabled = $card->isEnabled();
        $dailyLimit = (float) $card->daily_spending_limit;
        $dailyPct = $dailyLimit > 0 ? min(100, ((float) $card->daily_spent / $dailyLimit) * 100) : 0;
        $tone = $dailyPct >= 90 ? 'is-bad' : ($dailyPct >= 70 ? 'is-warn' : '');
    @endphp
    <section class="nvm-card nvm-box" aria-labelledby="nvm-nfc-c{{ $card->id }}">
        <div class="nvm-sn-cardrow">
            <div class="nvm-sn-nfc {{ $enabled ? '' : 'is-off' }}">
                <div class="nvm-sn-nfc__top">
                    <span class="nvm-sn-nfc__chip" aria-hidden="true"></span>
                    <span class="nvm-sn-nfc__brand nvm-latin">NFC · {{ $card->card_type_label }}</span>
                </div>
                <div class="nvm-sn-nfc__no">{{ $card->masked_card_number }}</div>
                <div class="nvm-sn-nfc__foot">
                    <div><small>ชื่อบัตร</small><b id="nvm-nfc-c{{ $card->id }}">{{ $card->card_name ?? 'การ์ด NFC' }}</b></div>
                    <div class="nvm-sn-nfc__bal"><small>ยอดคงเหลือ</small><b class="nvm-num">{{ $baht($card->balance) }}</b></div>
                </div>
            </div>

            <div>
                <div class="nvm-sn-chips">
                    <span class="nvm-chip {{ $chip($card->status_badge_color) ? 'nvm-chip--'.$chip($card->status_badge_color) : '' }}">{{ $card->status_label }}</span>
                    <span class="nvm-chip {{ $enabled ? 'nvm-chip--ok' : 'nvm-chip--bad' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $enabled ? 'check' : 'x' }}"/></svg>{{ $enabled ? 'เปิดใช้งาน' : 'ปิดใช้งานอยู่' }}</span>
                    <span class="nvm-chip {{ $card->hasLinkedWallet() ? 'nvm-chip--ok' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>{{ $card->hasLinkedWallet() ? 'ผูกกระเป๋าแล้ว' : 'ยังไม่ผูกกระเป๋า' }}</span>
                </div>

                <div class="nvm-meter">
                    <div class="nvm-meter__row"><span>ใช้ไปวันนี้</span><b class="nvm-num">{{ $baht($card->daily_spent) }} / {{ $baht($dailyLimit) }}</b></div>
                    <div class="nvm-meter__bar nvm-sn-w {{ $tone }}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($dailyPct) }}" aria-label="วงเงินรายวันที่ใช้ไป" style="--w: {{ round($dailyPct, 1) }}%"><i></i></div>
                    <small class="nvm-num">คงเหลือวันนี้ {{ $baht($card->daily_remaining) }}</small>
                </div>

                <div class="nvm-minis nvm-gap-t">
                    <div class="nvm-mini"><small>วงเงิน/รายการ</small><b class="nvm-num">{{ $baht($card->transaction_limit) }}</b></div>
                    <div class="nvm-mini"><small>วงเงินรายเดือน</small><b class="nvm-num">{{ $baht($card->monthly_spending_limit) }}</b></div>
                    <div class="nvm-mini"><small>เติมเงินอัตโนมัติ</small><b>{{ $card->auto_topup_enabled ? 'เปิด' : 'ปิด' }}</b></div>
                    <div class="nvm-mini"><small>ใช้งานล่าสุด</small><b>{{ $card->last_used_at ? $card->last_used_at->locale('th')->diffForHumans() : 'ยังไม่เคยใช้' }}</b></div>
                </div>

                <div class="nvm-sn-acts">
                    <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.nfc.show', $card) }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>จัดการการ์ด</a>
                    @if($card->hasLinkedWallet())
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="openTopUp(@js(route('user.nfc.topup', $card)), @js($card->card_name ?? 'การ์ด NFC'))"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เติมเงิน</button>
                    @else
                        <form action="{{ route('user.nfc.wallet.link', $card) }}" method="POST">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ผูกกระเป๋าเงิน</button>
                        </form>
                    @endif
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.nfc.transactions', $card) }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติ</a>
                    @if($enabled)
                        <form action="{{ route('user.nfc.disable', $card) }}" method="POST" @submit="if (! confirm('ปิดการใช้งานการ์ดนี้? ระหว่างปิดจะแตะจ่ายไม่ได้ เปิดกลับได้ทุกเมื่อ')) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ปิดการใช้งาน</button>
                        </form>
                    @else
                        <form action="{{ route('user.nfc.enable', $card) }}" method="POST">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>เปิดใช้งาน</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if($card->transactions->isNotEmpty())
            <div class="nvm-sec-h nvm-gap-t">
                <div><small class="nvm-latin">Recent</small><h2>ธุรกรรมล่าสุด</h2></div>
                <a href="{{ route('user.nfc.transactions', $card) }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            <ul class="nvm-txs">
                @foreach($card->transactions->take(3) as $tx)
                    @include('user.nfc.partials.tx', ['tx' => $tx])
                @endforeach
            </ul>
        @endif
    </section>
@empty
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีการ์ด NFC</b>
            คุณยังไม่มีการ์ด NFC ในระบบ ติดต่อทีมงานเพื่อขอรับการ์ดได้เลย
            @if(\Illuminate\Support\Facades\Route::has('user.tickets.index'))
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.tickets.index') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ติดต่อฝ่ายสนับสนุน</a></div>
            @endif
        </div>
    </section>
@endforelse

@include('user.nfc.partials.topup-modal', ['walletBalance' => $wallet->balance ?? 0])

</div>
@endsection

@push('scripts')
<script>
/**
 * หน้ารวมการ์ด NFC — เปิดโมดัลเติมเงินของการ์ดที่เลือก (ฟอร์มเดียวใช้ร่วมทุกใบ เปลี่ยน action ตามการ์ด)
 */
function nvmNfcCards() {
    return {
        topUp: false,
        topUpAction: '',
        topUpName: '',

        openTopUp(action, name) {
            this.topUpAction = action;
            this.topUpName = name;
            // ช่องจำนวนเงินในโมดัลโฟกัสตัวเองผ่าน x-effect
            this.topUp = true;
        },
    };
}
</script>
@endpush
