{{--
    การจองบริการของฉัน (ธีมโนวาเต็ม)
    ข้อมูลจาก User\ServiceBookingController::myBookings — $bookings (paginator ของสมาชิกคนนี้), $stats [total, waiting, active, completed, cancelled], $status (แท็บที่เลือก)
    ยกเลิก POST user.bookings.cancel {reason}* — หน้าต่างยืนยันในหน้านี้ · ติดตาม GET user.bookings.track (หน้าแผนที่)
--}}
@extends('layouts.user-nova')

@section('title', 'การจองของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $bkStatus = $status ?? '';
    $bkTabs = [
        '' => ['ทั้งหมด', $stats['total'] ?? 0],
        'waiting' => ['รอดำเนินการ', $stats['waiting'] ?? 0],
        'active' => ['กำลังทำ', $stats['active'] ?? 0],
        'completed' => ['เสร็จสิ้น', $stats['completed'] ?? 0],
        'cancelled' => ['ยกเลิก', $stats['cancelled'] ?? 0],
    ];
    // โทนชิปสถานะการจอง
    $bkTone = fn (?string $s) => match ($s) {
        'pending', 'notifying_provider', 'waiting_provider' => 'warn',
        'paid', 'provider_accepted' => 'info',
        'provider_on_way', 'in_progress' => 'gold',
        'completed' => 'ok',
        'cancelled', 'provider_rejected' => 'bad',
        default => '',
    };
    $bkTrackable = ['provider_accepted', 'provider_on_way', 'in_progress'];
    $bkFmt = fn ($d) => $d ? $d->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-';
@endphp

@section('content')

<div class="nvm-stack" x-data="{ cancel: null, reason: '', busy: false }" @keydown.escape.window="cancel = null">

<section class="nvm-ph" aria-labelledby="nvm-os-bk-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My bookings</div>
            <h1 id="nvm-os-bk-h">การจองของฉัน</h1>
            <p class="nvm-ph__lead">ดูสถานะ ติดตามผู้ให้บริการ และจัดการการจองบริการทั้งหมดของคุณ</p>
            <div class="nvm-bal">
                <small>การจองทั้งหมด</small>
                <b class="nvm-foil nvm-num">{{ number_format((int) ($stats['total'] ?? 0)) }}<span> รายการ</span></b>
            </div>
            @if(RouteFacade::has('user.services.index'))
                <div class="nvm-os-acts nvm-os-acts--ph">
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>จองบริการใหม่</a>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-kpis">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">รอดำเนินการ <span class="nvm-kpi__en nvm-latin">Waiting</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($stats['waiting'] ?? 0)) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--warn">รอชำระ/รอผู้ให้บริการ</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">กำลังทำ <span class="nvm-kpi__en nvm-latin">Active</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($stats['active'] ?? 0)) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--gold">ติดตามได้</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">เสร็จสิ้น <span class="nvm-kpi__en nvm-latin">Done</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($stats['completed'] ?? 0)) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--ok">ให้คะแนนได้</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ยกเลิก <span class="nvm-kpi__en nvm-latin">Cancelled</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($stats['cancelled'] ?? 0)) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip">ยกเลิก/ถูกปฏิเสธ</span></div>
    </div>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-os-bkl-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Bookings</small><h2 id="nvm-os-bkl-h">รายการจอง</h2></div>
        <nav class="nvm-tabs" aria-label="กรองสถานะการจอง">
            @foreach($bkTabs as $key => [$label, $count])
                <a href="{{ route('user.bookings.index', $key === '' ? [] : ['status' => $key]) }}" class="{{ $bkStatus === $key ? 'is-on' : '' }}" @if($bkStatus === $key) aria-current="page" @endif>
                    {{ $label }}<span class="nvm-chip nvm-num">{{ number_format((int) $count) }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if($bookings->count())
        <div class="nvm-os-list">
            @foreach($bookings as $booking)
                @php $tn = $bkTone($booking->status); @endphp
                <article class="nvm-card nvm-os-order" aria-label="การจอง #{{ $booking->booking_number }}">
                    <div class="nvm-os-order__head">
                        <div>
                            <div class="nvm-os-order__no">
                                <a href="{{ route('user.bookings.show', $booking) }}">{{ $booking->service?->name ?? 'บริการ' }}</a>
                                <span class="nvm-chip {{ $tn ? 'nvm-chip--'.$tn : '' }}">{{ $booking->status_text }}</span>
                            </div>
                            <div class="nvm-os-order__meta nvm-num">#{{ $booking->booking_number }}@if($booking->service?->category) · {{ $booking->service->category->name }}@endif</div>
                        </div>
                        <div class="nvm-os-order__amt">
                            <small>ราคารวม</small>
                            {{-- 🐛 เดิมอ่าน total_price (ไม่มีคอลัมน์นี้) ยอดเลยขึ้น ฿0.00 ทุกรายการ --}}
                            <b class="nvm-num">฿{{ number_format((float) $booking->total_amount, 2) }}</b>
                        </div>
                    </div>

                    <ul class="nvm-os-lines">
                        <li><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><span class="nvm-num">นัดหมาย {{ $bkFmt($booking->scheduled_at) }}</span></li>
                        @if($booking->provider)
                            <li><svg class="nvm-ico"><use href="#nvm-i-user"/></svg><span>{{ $booking->provider->display_name ?: $booking->provider->name }}</span></li>
                        @elseif(! in_array($booking->status, ['cancelled', 'completed', 'provider_rejected'], true))
                            <li class="is-note"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg><span>กำลังหาผู้ให้บริการ…</span></li>
                        @endif
                    </ul>

                    <div class="nvm-os-acts">
                        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.bookings.show', $booking) }}"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูรายละเอียด</a>
                        @if(in_array($booking->status, $bkTrackable, true))
                            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.bookings.track', $booking) }}"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ติดตาม</a>
                        @endif
                        @if($booking->status === 'completed' && ! $booking->rating)
                            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.bookings.show', $booking) }}#review"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ให้คะแนน</a>
                        @endif
                        @if($booking->canCancel())
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm nvm-os-btn-bad"
                                    @click="cancel = { url: @js(route('user.bookings.cancel', $booking)), no: @js($booking->booking_number), paid: {{ $booking->payment_status === 'paid' ? 'true' : 'false' }} }; reason = ''">
                                <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิก
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($bookings->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $bookings->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $bkStatus === '' ? 'ยังไม่มีการจอง' : 'ไม่มีการจองในสถานะนี้' }}</b>
            {{ $bkStatus === '' ? 'ค้นหาบริการที่ต้องการแล้วจองได้เลย' : 'ลองเลือกสถานะอื่น' }}
            @if($bkStatus === '' && RouteFacade::has('user.services.index'))
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหาบริการ</a></div>
            @endif
        </div>
    @endif
</section>

{{-- หน้าต่างยืนยันยกเลิก — 🐛 เดิมไม่มีช่องเหตุผล (คอนโทรลเลอร์บังคับ reason) จึงยกเลิกจากหน้านี้ไม่ได้เลย
     และชื่อ showCancelModal ถูกใช้ทั้งเป็นตัวแปรและเมธอด หน้าต่างจึงเปิดค้างตั้งแต่โหลดหน้า --}}
<div class="nvm-modal nvm-os-modal" x-show="cancel" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-os-bk-cc-h" @click.self="cancel = null">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-os-bk-cc-h"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยกเลิกการจอง?</h2>
        <p class="nvm-desc nvm-gap-t">การจอง <b class="nvm-num" x-text="cancel && ('#' + cancel.no)"></b> จะถูกยกเลิกและย้อนกลับไม่ได้</p>
        <p class="nvm-note" x-show="cancel && cancel.paid"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ชำระเงินแล้ว — ระบบคืนเงินเข้ากระเป๋าเงินของคุณอัตโนมัติ</p>
        <form method="POST" :action="cancel ? cancel.url : ''" class="nvm-form" @submit="if (busy || reason.trim() === '') { $event.preventDefault(); return; } busy = true">
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="bk-cancel-reason">เหตุผลในการยกเลิก <em>*</em></label>
                <textarea id="bk-cancel-reason" name="reason" rows="3" maxlength="500" required class="nvm-input" x-model="reason" placeholder="เช่น เปลี่ยนวันนัด ไม่สะดวกแล้ว"></textarea>
            </div>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="cancel = null">ปิด</button>
                <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy || reason.trim() === ''"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยืนยันยกเลิก</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
