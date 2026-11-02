{{--
    ประวัติธุรกรรมคริปโต (ธีมโนวาเต็ม)
    CryptoWalletController::transactions → $transactions (paginate 20 · ของสมาชิกคนนี้) · $currencies (ตัวเลือกตัวกรอง)
    ตัวกรอง type / currency / status — ค่าว่าง = ทั้งหมด (controller ใช้ filled() แล้ว)
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติธุรกรรมคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $types = [
        'deposit' => 'ฝาก',
        'withdrawal' => 'ถอน',
        'exchange_buy' => 'ซื้อเหรียญ',
        'exchange_sell' => 'ขายเหรียญ',
        'transfer_in' => 'รับจากสมาชิก',
        'transfer_out' => 'ส่งให้สมาชิก',
    ];
    $statuses = [
        'pending' => 'รอดำเนินการ',
        'confirming' => 'รอการยืนยัน',
        'completed' => 'สำเร็จ',
        'failed' => 'ล้มเหลว',
        'cancelled' => 'ยกเลิกแล้ว',
    ];
    $filtered = request()->filled('type') || request()->filled('currency') || request()->filled('status');
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Transactions</div>
            <h1 id="nvm-cwt-h">ประวัติธุรกรรม</h1>
            <p class="nvm-ph__lead">ธุรกรรมคริปโตทั้งหมดของคุณ ฝาก ถอน ซื้อ และขายเหรียญ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>{{ number_format($transactions->total()) }} รายการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-label="ตัวกรองธุรกรรม">
    <form method="GET" action="{{ route('user.crypto-wallet.transactions') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="cw-f-type">ประเภท</label>
            <select id="cw-f-type" name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="cw-f-cur">สกุลเงิน</label>
            <select id="cw-f-cur" name="currency" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($currencies as $curr)
                    <option value="{{ $curr->code }}" @selected(request('currency') === $curr->code)>{{ $curr->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="cw-f-st">สถานะ</label>
            <select id="cw-f-st" name="status" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            @if($filtered)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.crypto-wallet.transactions') }}">ล้าง</a>
            @endif
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
        </div>
    </form>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-cwt-list-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">History</small><h2 id="nvm-cwt-list-h">รายการธุรกรรม</h2></div></div>
    @if($transactions->count() > 0)
        <div class="nvm-table-wrap">
            <table class="nvm-table nvm-cw-table">
                <thead>
                    <tr>
                        <th scope="col">ประเภท</th>
                        <th scope="col">สกุลเงิน</th>
                        <th scope="col" class="r">จำนวน</th>
                        <th scope="col">สถานะ</th>
                        <th scope="col">เวลา</th>
                        <th scope="col" class="r"><span class="nvm-sr">จัดการ</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        @php $txCode = $tx->currency?->code ?? '-'; @endphp
                        <tr>
                            <td>
                                <b>{{ $tx->typeLabel() }}</b>
                                <div class="nvm-cw-mono nvm-cw-muted">{{ \Illuminate\Support\Str::limit((string) $tx->transaction_id, 14) }}</div>
                            </td>
                            <td>
                                <div class="nvm-cw-cell">
                                    @include('user.crypto-wallet.partials.coin', ['code' => $txCode])
                                    <div><b>{{ $txCode }}</b><small>{{ strtoupper((string) $tx->network) }}</small></div>
                                </div>
                            </td>
                            <td class="r">
                                <b class="nvm-num {{ $tx->is_incoming ? 'nvm-cw-up' : 'nvm-cw-down' }}">{{ $tx->is_incoming ? '+' : '-' }}{{ number_format((float) $tx->amount, 8) }}</b>
                                @if($tx->amount_thb)<div class="nvm-num nvm-cw-muted">≈ ฿{{ number_format((float) $tx->amount_thb, 2) }}</div>@endif
                            </td>
                            <td>
                                @include('user.crypto-wallet.partials.status', ['status' => $tx->status])
                                @if(in_array($tx->status, ['pending', 'confirming'], true) && $tx->confirmations > 0)
                                    <div class="nvm-num nvm-cw-muted">{{ (int) $tx->confirmations }}/{{ (int) $tx->confirmations_required }} การยืนยัน</div>
                                @endif
                            </td>
                            <td class="nvm-num">
                                {{ $tx->created_at?->format('d/m/Y') }}
                                <div class="nvm-cw-muted">{{ $tx->created_at?->format('H:i') }}</div>
                            </td>
                            <td class="r"><a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.crypto-wallet.transaction.detail', $tx->id) }}">ดู</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $transactions->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ไม่พบธุรกรรม</b>
            {{ $filtered ? 'ลองเปลี่ยนหรือล้างตัวกรอง' : 'เริ่มต้นด้วยการฝากหรือแลกเปลี่ยนเหรียญ' }}
        </div>
    @endif
</section>

</div>
@endsection
