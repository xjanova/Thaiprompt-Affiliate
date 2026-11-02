{{--
    ประวัติธุรกรรมการ์ด NFC (ธีมโนวาเต็ม) — NFCCardController::transactions (การ์ดของสมาชิกเองเท่านั้น ไม่ใช่ = 403)
    $transactions แบ่งหน้า 20 พร้อมจุดรับชำระ (nfcReader) · ตารางอยู่ใน nvm-table-wrap เลื่อนแนวนอนได้บนมือถือ
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติการใช้งาน · '.($card->card_name ?? 'การ์ด NFC'))

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-nfct-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.nfc.show', $card) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>{{ $card->card_name ?? 'การ์ด NFC' }}</a>
            <div class="nvm-eyebrow nvm-latin">Card history</div>
            <h1 id="nvm-nfct-h">ประวัติการทำธุรกรรม</h1>
            <p class="nvm-ph__lead nvm-num">{{ $card->card_name ?? 'การ์ด NFC' }} · {{ $card->masked_card_number }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold nvm-num">ยอดคงเหลือ {{ $baht($card->balance) }}</span>
                <span class="nvm-pill nvm-pill--glass"><span class="nvm-num">{{ number_format($transactions->total()) }}</span>&nbsp;รายการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-nfct-l-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Transactions</small><h2 id="nvm-nfct-l-h">รายการทั้งหมด</h2></div></div>

    @if($transactions->count() > 0)
        <div class="nvm-table-wrap">
            <table class="nvm-table">
                <thead>
                    <tr>
                        <th scope="col">เวลา</th>
                        <th scope="col">ประเภท</th>
                        <th scope="col">สถานที่</th>
                        <th scope="col" class="r">จำนวนเงิน</th>
                        <th scope="col" class="r">ยอดคงเหลือ</th>
                        <th scope="col" class="c">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        @php
                            $out = $tx->type === 'payment';
                            $tone = match ($tx->status) {
                                'completed' => 'ok', 'failed' => 'bad', 'pending', 'processing' => 'warn', 'refunded' => 'info', default => '',
                            };
                        @endphp
                        <tr>
                            <td class="nvm-num">
                                {{ $tx->created_at?->format('d/m/Y') }}
                                <span class="nvm-sn-sub">{{ $tx->created_at?->format('H:i:s') }}</span>
                            </td>
                            <td>
                                {{ $tx->type_label }}
                                <span class="nvm-sn-sub nvm-num">#{{ $tx->transaction_id }}</span>
                            </td>
                            <td>{{ $tx->nfcReader->name ?? ($tx->location ?: '-') }}</td>
                            <td class="r"><span class="nvm-sn-amt nvm-num {{ $out ? 'is-out' : 'is-in' }}">{{ $out ? '-' : '+' }}{{ $baht($tx->amount) }}</span></td>
                            <td class="r nvm-num">{{ $baht($tx->balance_after) }}</td>
                            <td class="c"><span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $tx->status_label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $transactions->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีธุรกรรม</b>
            ยังไม่มีประวัติการทำธุรกรรมสำหรับการ์ดนี้
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.nfc.show', $card) }}">กลับไปหน้าการ์ด</a></div>
        </div>
    @endif
</section>

</div>
@endsection
