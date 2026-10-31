@extends('layouts.user-nova')

@section('title', 'คำขอถอนเงินของฉัน')

@php
    // คำขอถอนเงิน — ธีมโนวาเต็ม
    // 🐛 (2026-10-29) เดิมอ่าน $statistics['total_amount'/'pending_count'/...] ซึ่ง service ไม่มี key พวกนี้
    //    → การ์ดสถิติเป็น 0 ตลอด · ตอนนี้ใช้ key จริงจาก WithdrawalService::getWithdrawalStatistics
    // 🐛 เดิมโชว์ "เสร็จ" จาก completed_at ซึ่งไม่มีคอลัมน์นี้ — ของจริงคือ transfer_completed_at
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $stats = $statistics ?? [];
    $doneTotal = (float) ($stats['total_completed'] ?? 0);
    [$doneInt, $doneDec] = explode('.', number_format($doneTotal, 2));
    $kpis = [
        ['images/nova/wallet/withdraw.webp', 'ยอดที่ขอถอนทั้งหมด', 'Requested', $baht($stats['total_requested'] ?? 0), null],
        ['images/nova/wallet/hourglass.webp', 'รอตรวจสอบ', 'Pending', number_format((int) ($stats['count_pending'] ?? 0)).' รายการ', 'ยอดรวม '.$baht($stats['total_pending'] ?? 0)],
        ['images/nova/wallet/bank.webp', 'โอนสำเร็จ', 'Completed', number_format((int) ($stats['count_completed'] ?? 0)).' รายการ', 'ค่าธรรมเนียมรวม '.$baht($stats['total_fees'] ?? 0)],
        ['images/nova/wallet/scale.webp', 'ถูกปฏิเสธ', 'Rejected', number_format((int) ($stats['count_rejected'] ?? 0)).' รายการ', 'เงินคืนเข้ากระเป๋าแล้ว'],
    ];
    $tone = fn ($s) => match ($s) {
        'pending' => 'nvm-chip--info',
        'processing' => 'nvm-chip--warn',
        'approved' => 'nvm-chip--gold',
        'completed' => 'nvm-chip--ok',
        'rejected' => 'nvm-chip--bad',
        default => '',
    };
    $last4 = fn ($no) => substr(preg_replace('/[^0-9A-Za-z]/', '', (string) $no), -4);
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-wds-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Withdrawals</div>
            <h1 id="nvm-wds-h">คำขอถอนเงินของฉัน</h1>
            <p class="nvm-ph__lead">ติดตามสถานะทุกคำขอ · ตรวจภายใน 24 ชั่วโมง โอนเข้าบัญชีเป็นรอบ วันที่ 2 และ 17 ของทุกเดือน</p>
            <div class="nvm-bal">
                <small>รับเข้าบัญชีแล้วทั้งหมด</small>
                <b class="nvm-foil nvm-num">฿{{ $doneInt }}<span>.{{ $doneDec }}</span></b>
            </div>
            @if($nvR('user.wallet.withdraw'))
                <div class="nvm-gap-t"><a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.withdraw') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ถอนเงิน</a></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปคำขอถอนเงิน">
    @foreach($kpis as [$img, $label, $en, $value, $foot])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            @if($foot)<div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>@endif
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-wdl-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Requests</small><h2 id="nvm-wdl-h">รายการคำขอถอนเงิน</h2></div></div>

        @if($withdrawals->isEmpty())
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีคำขอถอนเงิน</b>
                ถอนเงินเข้าบัญชีธนาคารหรือพร้อมเพย์ได้ตลอด ระบบจะโอนให้ตามรอบ
                @if($nvR('user.wallet.withdraw'))
                    <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.wallet.withdraw') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>สร้างคำขอถอนเงิน</a></div>
                @endif
            </div>
        @else
            <ul class="nvm-txs">
                @foreach($withdrawals as $w)
                    @php
                        // ใช้ข้อมูลบัญชีที่บันทึกไว้ตอนยื่นคำขอก่อน — บัญชีในโปรไฟล์อาจถูกแก้/ลบไปแล้ว
                        $pd = is_array($w->payment_details) ? $w->payment_details : [];
                        $pmType = $pd['type'] ?? $w->payment_type;
                        $pmTitle = match ($pmType) {
                            'bank_transfer' => $pd['bank_name'] ?? optional($w->paymentMethod)->bank_name ?? 'โอนเข้าบัญชีธนาคาร',
                            'promptpay' => 'พร้อมเพย์',
                            'paypal' => 'PayPal',
                            default => $w->payment_type_label,
                        };
                        $pmNo = $pd['account_number'] ?? optional($w->paymentMethod)->account_number;
                    @endphp
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $pmType === 'promptpay' ? 'qr' : 'bank' }}"/></svg></span>
                        <div class="nvm-tx__t">
                            <b>{{ $pmTitle }}@if($pmNo) <span class="nvm-num">•••• {{ $last4($pmNo) }}</span>@endif</b>
                            <small class="nvm-num">{{ $w->request_id }}</small>
                            @if($w->fee > 0 || $w->tax > 0)
                                <small class="nvm-num">ขอถอน {{ $baht($w->amount) }}@if($w->fee > 0) · ค่าธรรมเนียม {{ $baht($w->fee) }}@endif @if($w->tax > 0) · ภาษี {{ $baht($w->tax) }}@endif</small>
                            @endif
                            @if($w->user_note)<small>หมายเหตุ: {{ \Illuminate\Support\Str::limit($w->user_note, 80) }}</small>@endif
                            @if($w->isRejected() && $w->rejection_reason)
                                <small class="nvm-err">เหตุผลที่ปฏิเสธ: {{ $w->rejection_reason }}</small>
                            @endif
                            @if($w->transfer_completed_at)
                                <small>โอนเมื่อ <span class="nvm-num">{{ $w->transfer_completed_at->format('d/m/Y H:i') }}</span></small>
                            @endif
                        </div>
                        <div class="nvm-tx__r">
                            <span class="nvm-tx__amt nvm-num {{ in_array($w->status, ['rejected', 'cancelled'], true) ? 'is-void' : '' }}">{{ $baht($w->net_amount) }}</span>
                            <span class="nvm-chip {{ $tone($w->status) }}">{{ $w->status_label }}</span>
                            <time class="nvm-num" datetime="{{ $w->created_at->toIso8601String() }}">{{ $w->created_at->format('d/m/Y H:i') }}</time>
                            @if($w->isPending())
                                {{-- ยกเลิกต้องยืนยันอีกครั้ง (กดพลาดบนมือถือ = เสียคิวรอบโอน) --}}
                                <form method="POST" action="{{ route('user.wallet.withdrawal.cancel', $w->id) }}" class="nvm-tx__act" x-data="{ ask: false, busy: false }" @submit="busy = true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="nvm-link" x-show="! ask" @click="ask = true">ยกเลิกคำขอ</button>
                                    <span x-show="ask" x-cloak>
                                        <button type="submit" class="nvm-btn nvm-btn--sm nvm-btn--bad" :disabled="busy" x-text="busy ? 'กำลังยกเลิก…' : 'ยืนยันยกเลิก'">ยืนยันยกเลิก</button>
                                        <button type="button" class="nvm-link" @click="ask = false" :disabled="busy">ไม่ใช่</button>
                                    </span>
                                </form>
                            @elseif($w->transfer_slip_url)
                                <a class="nvm-link" href="{{ $w->transfer_slip_url }}" target="_blank" rel="noopener">ดูสลิปโอน</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($withdrawals->hasPages())
                <div class="nvm-pager">{{ $withdrawals->links() }}</div>
            @endif
        @endif
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-wst-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-wst-h">สถานะคำขอ</h2></div></div>
            <dl class="nvm-kv nvm-kv--legend">
                <dt><span class="nvm-chip nvm-chip--info">รอดำเนินการ</span></dt><dd>ยังไม่ได้ตรวจ · ยกเลิกเองได้</dd>
                <dt><span class="nvm-chip nvm-chip--gold">อนุมัติแล้ว</span></dt><dd>รอโอนตามรอบ</dd>
                <dt><span class="nvm-chip nvm-chip--warn">กำลังดำเนินการ</span></dt><dd>กำลังโอนเงิน</dd>
                <dt><span class="nvm-chip nvm-chip--ok">สำเร็จ</span></dt><dd>เงินเข้าบัญชีแล้ว</dd>
                <dt><span class="nvm-chip nvm-chip--bad">ถูกปฏิเสธ</span></dt><dd>เงินคืนเข้ากระเป๋า</dd>
            </dl>
        </aside>
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-wtm-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Timeline</small><h2 id="nvm-wtm-h">ระยะเวลาดำเนินการ</h2></div></div>
            <ol class="nvm-steps">
                <li>ยื่นคำขอ — ระบบกันยอดออกจากกระเป๋าทันที</li>
                <li>ทีมงานตรวจสอบภายใน 24 ชั่วโมง</li>
                <li>โอนเข้าบัญชีเป็นรอบ วันที่ 2 และ 17 ของทุกเดือน</li>
                <li>มีปัญหา ติดต่อฝ่ายสนับสนุน พร้อมรหัสคำขอ</li>
            </ol>
        </aside>
    </div>
</div>
@endsection
