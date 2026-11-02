{{--
    รายละเอียดการ์ด NFC (ธีมโนวาเต็ม) — NFCCardController::show (ตรวจว่าเป็นการ์ดของสมาชิกเองแล้ว ไม่ใช่ = 403)
    $card (โหลด wallet + ธุรกรรมล่าสุด 20 รายการ) · $statistics จาก NFCCardService::getCardStatistics
    โมดัล 3 ตัว: เติมเงิน (POST topup) · วงเงิน (PUT limits) · เติมเงินอัตโนมัติ (PUT auto-topup)
    - เติมเงินอัตโนมัติ: เดิมติ๊กออกแล้วฟอร์มไม่ส่งช่อง enabled เลย → ตก validation (required) ปิดไม่ได้
      ตอนนี้มี hidden enabled=0 นำหน้าช่องติ๊ก
    - ธุรกรรมล่าสุดที่ controller โหลดมาแล้วแต่หน้าเดิมไม่แสดง → แสดง 10 รายการแรก
--}}
@extends('layouts.user-nova')

@section('title', ($card->card_name ?? 'การ์ด NFC').' · การ์ด NFC')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $enabled = $card->isEnabled();
    $pct = fn ($used, $limit) => (float) $limit > 0 ? min(100, ((float) $used / (float) $limit) * 100) : 0;
    $tone = fn ($p) => $p >= 90 ? 'is-bad' : ($p >= 70 ? 'is-warn' : '');
    $dailyPct = $pct($card->daily_spent, $card->daily_spending_limit);
    $monthlyPct = $pct($card->monthly_spent, $card->monthly_spending_limit);
    $statusChip = match ($card->status_badge_color) {
        'green' => 'ok', 'red' => 'bad', 'orange', 'yellow' => 'warn', default => '',
    };
    $stats = is_array($statistics ?? null) ? $statistics : [];
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y') : null;

    // เปิดโมดัลที่ส่งไม่ผ่าน validation ค้างไว้ ให้เห็นข้อความผิดพลาดทันที
    $reopen = match (true) {
        $errors->hasAny(['daily_limit', 'monthly_limit', 'transaction_limit']) => 'limits',
        $errors->hasAny(['threshold', 'enabled']) || ($errors->has('amount') && old('threshold') !== null) => 'auto',
        $errors->hasAny(['amount', 'pin']) => 'topup',
        default => null,
    };
@endphp

@section('content')
<div class="nvm-stack" x-data="{ topUp: @js($reopen === 'topup'), limits: @js($reopen === 'limits'), auto: @js($reopen === 'auto'), autoOn: @js((bool) old('enabled', $card->auto_topup_enabled)) }">

<section class="nvm-ph" aria-labelledby="nvm-nfcs-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.nfc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>การ์ดทั้งหมด</a>
            <div class="nvm-eyebrow nvm-latin">NFC card</div>
            <h1 id="nvm-nfcs-h">{{ $card->card_name ?? 'การ์ด NFC' }}</h1>
            <p class="nvm-ph__lead nvm-num">{{ $card->masked_card_number }} · {{ $card->card_type_label }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $enabled ? 'ok' : 'bad' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $enabled ? 'check' : 'x' }}"/></svg>{{ $enabled ? 'เปิดใช้งาน' : 'ปิดใช้งานอยู่' }}</span>
                <span class="nvm-pill nvm-pill--glass">{{ $card->status_label }}</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-nfcs-bal-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Balance</small><h2 id="nvm-nfcs-bal-h">ยอดเงินในการ์ด</h2></div></div>
            <div class="nvm-sn-nfc {{ $enabled ? '' : 'is-off' }}">
                <div class="nvm-sn-nfc__top">
                    <span class="nvm-sn-nfc__chip" aria-hidden="true"></span>
                    <span class="nvm-sn-nfc__brand nvm-latin">NFC · {{ $card->card_type_label }}</span>
                </div>
                <div class="nvm-sn-nfc__no">{{ $card->masked_card_number }}</div>
                <div class="nvm-sn-nfc__foot">
                    <div><small>ชื่อบัตร</small><b>{{ $card->card_name ?? 'การ์ด NFC' }}</b></div>
                    <div class="nvm-sn-nfc__bal"><small>ยอดคงเหลือ</small><b class="nvm-num">{{ $baht($card->balance) }}</b></div>
                </div>
            </div>
            <div class="nvm-sn-acts">
                @if($card->hasLinkedWallet())
                    <button type="button" class="nvm-btn nvm-btn--gold" @click="topUp = true; $nextTick(() => $refs.topUpAmount.focus())"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เติมเงินจากกระเป๋า</button>
                @else
                    <form action="{{ route('user.nfc.wallet.link', $card) }}" method="POST">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ผูกกระเป๋าเงิน</button>
                    </form>
                @endif
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.nfc.transactions', $card) }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติทั้งหมด</a>
            </div>
            @if($card->hasLinkedWallet())
                <p class="nvm-note nvm-num"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ผูกกับกระเป๋าเงินแล้ว · ยอดกระเป๋า {{ $baht($card->wallet->balance ?? 0) }}</p>
            @endif
            <div class="nvm-minis nvm-gap-t">
                <div class="nvm-mini"><small>ธุรกรรมทั้งหมด</small><b class="nvm-num">{{ number_format((int) ($stats['total_transactions'] ?? 0)) }}</b></div>
                <div class="nvm-mini"><small>ธุรกรรมสำเร็จ</small><b class="nvm-num">{{ number_format((int) ($stats['completed_transactions'] ?? 0)) }}</b></div>
                <div class="nvm-mini"><small>ยอดใช้จ่ายรวม</small><b class="nvm-num is-out">{{ $baht($stats['total_spent'] ?? 0) }}</b></div>
                <div class="nvm-mini"><small>เติมเงินรวม</small><b class="nvm-num is-in">{{ $baht($stats['total_topped_up'] ?? 0) }}</b></div>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-nfcs-tx-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Recent activity</small><h2 id="nvm-nfcs-tx-h">ธุรกรรมล่าสุด</h2></div>
                @if($card->transactions->isNotEmpty())
                    <a href="{{ route('user.nfc.transactions', $card) }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                @endif
            </div>
            @if($card->transactions->isNotEmpty())
                <ul class="nvm-txs">
                    @foreach($card->transactions->take(10) as $tx)
                        @include('user.nfc.partials.tx', ['tx' => $tx])
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีธุรกรรม</b>
                    เมื่อแตะจ่ายหรือเติมเงิน รายการจะแสดงที่นี่
                </div>
            @endif
        </section>
    </div>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-nfcs-st-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-nfcs-st-h">สถานะการ์ด</h2></div></div>
            <dl class="nvm-kv">
                <dt>สถานะ</dt><dd><span class="nvm-chip {{ $statusChip ? 'nvm-chip--'.$statusChip : '' }}">{{ $card->status_label }}</span></dd>
                <dt>การใช้งาน</dt><dd>{{ $enabled ? 'เปิดอยู่' : 'ปิดอยู่' }}</dd>
                <dt>ใช้งานล่าสุด</dt><dd>{{ $card->last_used_at ? $card->last_used_at->copy()->locale('th')->diffForHumans() : 'ยังไม่เคยใช้' }}</dd>
                <dt>หมดอายุ</dt><dd class="nvm-num">{{ $thDate($card->expires_at) ?? 'ไม่มีวันหมดอายุ' }}</dd>
            </dl>
            <div class="nvm-gap-t">
                @if($enabled)
                    <form action="{{ route('user.nfc.disable', $card) }}" method="POST" @submit="if (! confirm('ปิดการใช้งานการ์ดนี้? ระหว่างปิดจะแตะจ่ายไม่ได้ เปิดกลับได้ทุกเมื่อ')) $event.preventDefault()">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--line nvm-w-full"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ปิดการใช้งานการ์ด</button>
                    </form>
                @else
                    <form action="{{ route('user.nfc.enable', $card) }}" method="POST">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--gold nvm-w-full"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>เปิดใช้งานการ์ด</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-nfcs-lim-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Limits</small><h2 id="nvm-nfcs-lim-h">วงเงินการใช้จ่าย</h2></div>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="limits = true"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>แก้ไข</button>
            </div>
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>รายวัน</span><b class="nvm-num">{{ $baht($card->daily_spent) }} / {{ $baht($card->daily_spending_limit) }}</b></div>
                <div class="nvm-meter__bar nvm-sn-w {{ $tone($dailyPct) }}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($dailyPct) }}" aria-label="วงเงินรายวันที่ใช้ไป" style="--w: {{ round($dailyPct, 1) }}%"><i></i></div>
                <small class="nvm-num">คงเหลือ {{ $baht($card->daily_remaining) }}</small>
            </div>
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>รายเดือน</span><b class="nvm-num">{{ $baht($card->monthly_spent) }} / {{ $baht($card->monthly_spending_limit) }}</b></div>
                <div class="nvm-meter__bar nvm-sn-w {{ $tone($monthlyPct) }}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($monthlyPct) }}" aria-label="วงเงินรายเดือนที่ใช้ไป" style="--w: {{ round($monthlyPct, 1) }}%"><i></i></div>
                <small class="nvm-num">คงเหลือ {{ $baht($card->monthly_remaining) }}</small>
            </div>
            <dl class="nvm-kv nvm-gap-t"><dt>วงเงินต่อรายการ</dt><dd class="nvm-num">{{ $baht($card->transaction_limit) }}</dd></dl>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-nfcs-auto-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Auto top-up</small><h2 id="nvm-nfcs-auto-h">เติมเงินอัตโนมัติ</h2></div>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="auto = true"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>ตั้งค่า</button>
            </div>
            @if($card->auto_topup_enabled)
                <div class="nvm-status">
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                    <span>เปิดใช้งานอยู่<small class="nvm-num">ยอดต่ำกว่า {{ $baht($card->auto_topup_threshold) }} เติมให้ครั้งละ {{ $baht($card->auto_topup_amount) }}</small></span>
                </div>
            @else
                <div class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                    <span>ปิดอยู่<small>เปิดไว้ ระบบจะเติมเงินจากกระเป๋าให้เมื่อยอดในการ์ดต่ำ</small></span>
                </div>
            @endif
        </section>
    </div>
</div>

@if($card->hasLinkedWallet())
    @include('user.nfc.partials.topup-modal', [
        'walletBalance' => $card->wallet->balance ?? 0,
        'action' => route('user.nfc.topup', $card),
        'cardName' => $card->card_name ?? 'การ์ด NFC',
    ])
@endif

<div class="nvm-modal" x-show="limits" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-nfcs-lm-h" @click.self="limits = false" @keydown.escape.window="limits = false">
    <div class="nvm-card nvm-modal__box nvm-sn-modal">
        <h2 id="nvm-nfcs-lm-h">แก้ไขวงเงิน</h2>
        <form class="nvm-form nvm-gap-t" action="{{ route('user.nfc.limits.update', $card) }}" method="POST">
            @csrf
            @method('PUT')
            @foreach([
                ['daily_limit', 'วงเงินรายวัน (บาท)', $card->daily_spending_limit, 1000000],
                ['monthly_limit', 'วงเงินรายเดือน (บาท)', $card->monthly_spending_limit, 10000000],
                ['transaction_limit', 'วงเงินต่อรายการ (บาท)', $card->transaction_limit, 100000],
            ] as [$name, $label, $value, $max])
                <div class="nvm-field">
                    <label class="nvm-label" for="nvm-nfcs-{{ $name }}">{{ $label }} <em>*</em></label>
                    <input id="nvm-nfcs-{{ $name }}" class="nvm-input nvm-num @error($name) is-bad @enderror" type="number" name="{{ $name }}" required min="0" max="{{ $max }}" step="0.01" inputmode="decimal" value="{{ old($name, $value) }}">
                    @error($name)<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            @endforeach
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="limits = false">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div class="nvm-modal" x-show="auto" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-nfcs-at-h" @click.self="auto = false" @keydown.escape.window="auto = false">
    <div class="nvm-card nvm-modal__box nvm-sn-modal">
        <h2 id="nvm-nfcs-at-h">ตั้งค่าเติมเงินอัตโนมัติ</h2>
        <form class="nvm-form nvm-gap-t" action="{{ route('user.nfc.auto-topup.configure', $card) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="enabled" value="0">
            <label class="nvm-check">
                <input type="checkbox" name="enabled" value="1" x-model="autoOn">
                <span>เปิดใช้งานเติมเงินอัตโนมัติ</span>
            </label>
            @error('enabled')<span class="nvm-err">{{ $message }}</span>@enderror
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-nfcs-th">เติมเมื่อยอดในการ์ดต่ำกว่า (บาท)</label>
                <input id="nvm-nfcs-th" class="nvm-input nvm-num @error('threshold') is-bad @enderror" type="number" name="threshold" min="0" max="10000" step="0.01" inputmode="decimal" value="{{ old('threshold', $card->auto_topup_threshold) }}" :required="autoOn" :disabled="! autoOn">
                @error('threshold')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-nfcs-aa">จำนวนที่เติมแต่ละครั้ง (บาท)</label>
                <input id="nvm-nfcs-aa" class="nvm-input nvm-num @error('amount') is-bad @enderror" type="number" name="amount" min="100" max="100000" step="0.01" inputmode="decimal" value="{{ old('amount', $card->auto_topup_amount) }}" :required="autoOn" :disabled="! autoOn">
                @error('amount')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เติมขั้นต่ำครั้งละ ฿100 ระบบตัดเงินจากกระเป๋าเงินที่ผูกไว้</p>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="auto = false">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>บันทึก</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
