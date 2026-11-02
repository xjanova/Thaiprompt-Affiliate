{{--
    รายละเอียดการจองบริการ (ธีมโนวาเต็ม)
    ข้อมูลจาก User\ServiceBookingController::showBooking — $booking (ของสมาชิกคนนี้เท่านั้น ไม่ใช่ = 403)
      โหลด service.category, provider, items, locations, trackings
    - ยกเลิก POST user.bookings.cancel {reason}* · ติดตาม GET user.bookings.track · ให้คะแนน POST user.service-reviews.store {rating*, review}
    🐛 เดิมหน้านี้พัง 500 ทุกครั้ง (เรียก $booking->getStatusIcon() ที่ไม่มีอยู่จริง)
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดการจอง #'.$booking->booking_number)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $bk = $booking;
    $provider = $bk->provider;
    $fmt = fn ($d) => $d ? $d->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : null;
    $serviceLocation = $bk->locations->firstWhere('type', 'service_location');
    $trackable = in_array($bk->status, ['provider_accepted', 'provider_on_way', 'in_progress'], true);
    $cancelled = in_array($bk->status, ['cancelled', 'provider_rejected'], true);

    $pill = match ($bk->status) {
        'pending', 'notifying_provider', 'waiting_provider' => 'warn',
        'completed' => 'ok',
        'cancelled', 'provider_rejected' => 'bad',
        default => 'glass',
    };

    // ขั้นตอนสถานะ — สถานะที่ไม่อยู่ในเส้นหลักผูกเข้ากับขั้นที่ใกล้ที่สุด
    // (🐛 เดิม notifying_provider ไม่อยู่ในรายการ → array_search ได้ false ไทม์ไลน์เลยไม่ติ๊กขั้นไหนเลย)
    $flow = [
        'pending' => ['รอชำระเงิน', 'clock', $bk->created_at],
        'paid' => ['ชำระเงินแล้ว', 'wallet', null],
        'waiting_provider' => ['กำลังหาผู้ให้บริการ', 'search', $bk->provider_notified_at],
        'provider_accepted' => ['ผู้ให้บริการรับงาน', 'user', $bk->provider_accepted_at],
        'provider_on_way' => ['กำลังเดินทาง', 'scooter', null],
        'in_progress' => ['กำลังให้บริการ', 'gear', $bk->started_at],
        'completed' => ['เสร็จสิ้น', 'award', $bk->completed_at],
    ];
    $alias = ['notifying_provider' => 'waiting_provider'];
    $order = array_keys($flow);
    $current = array_search($alias[$bk->status] ?? $bk->status, $order, true);
    if ($cancelled || $current === false) {
        // ยกเลิก: ติ๊กเฉพาะขั้นที่มีเวลาบันทึกไว้จริง
        $current = -1;
    }

    $photo = $provider ? ($provider->profile_photo ?: $provider->avatar) : null;
    $photoUrl = $photo ? (str_starts_with($photo, 'http') ? $photo : asset('storage/'.ltrim($photo, '/'))) : null;
    $canReview = $bk->status === 'completed' && ! $bk->rating && RouteFacade::has('user.service-reviews.store');
@endphp

@section('content')
@if($serviceLocation && $serviceLocation->latitude && $serviceLocation->longitude)
    @include('shop.orders.partials.map')
@endif

<div class="nvm-stack" x-data="{ cancelOpen: {{ $errors->has('reason') ? 'true' : 'false' }}, reason: @js((string) old('reason', '')), busy: false }" @keydown.escape.window="cancelOpen = false">

<section class="nvm-ph" aria-labelledby="nvm-os-bs-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.bookings.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>การจองของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Booking detail</div>
            <h1 id="nvm-os-bs-h">{{ $bk->service?->name ?? 'บริการ' }}</h1>
            <p class="nvm-ph__lead nvm-num">#{{ $bk->booking_number }}@if($bk->service?->category) · {{ $bk->service->category->name }}@endif</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $pill }}">{{ $bk->status_text }}</span>
                @if($bk->scheduled_at)
                    <span class="nvm-pill nvm-pill--glass nvm-num"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>{{ $fmt($bk->scheduled_at) }} น.</span>
                @endif
                @if($bk->payment_status === 'paid')
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ชำระเงินแล้ว</span>
                @elseif($bk->payment_status === 'refunded')
                    <span class="nvm-pill nvm-pill--glass">คืนเงินแล้ว</span>
                @endif
            </div>
            <div class="nvm-os-ph-amt">
                <small>ราคารวม</small>
                <b class="nvm-foil nvm-num">฿{{ number_format((float) $bk->total_amount, 2) }}</b>
            </div>
            @if($trackable || $bk->canCancel() || $canReview)
                <div class="nvm-os-acts nvm-os-acts--ph">
                    @if($trackable)
                        <a class="nvm-btn nvm-btn--gold" href="{{ route('user.bookings.track', $bk) }}"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ติดตามตำแหน่ง</a>
                    @endif
                    @if($canReview)
                        <a class="nvm-btn nvm-btn--gold" href="#review"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ให้คะแนนบริการ</a>
                    @endif
                    @if($bk->canCancel())
                        <button type="button" class="nvm-btn nvm-btn--ghost" @click="cancelOpen = true"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิกการจอง</button>
                    @endif
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif
@if(session('success'))
    <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
        <div class="nvm-alert__txt"><b>สำเร็จ</b><p>{{ session('success') }}</p></div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">

        {{-- ── ผู้ให้บริการ ── --}}
        @if($provider)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-pv-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Provider</small><h2 id="nvm-os-bs-pv-h">ผู้ให้บริการ</h2></div></div>
                <div class="nvm-os-who">
                    <span class="nvm-os-who__av">@if($photoUrl)<img src="{{ $photoUrl }}" alt="" loading="lazy">@else<svg class="nvm-ico"><use href="#nvm-i-user"/></svg>@endif</span>
                    <div class="nvm-os-who__t">
                        <b>{{ $provider->display_name ?: $provider->name }}</b>
                        {{-- 🐛 เดิมอ่าน average_rating / reviews_count / profile_image (ไม่มีคอลัมน์) คะแนนและรูปไม่เคยแสดง --}}
                        @if((float) $provider->rating > 0)
                            <span class="nvm-os-rate"><i aria-hidden="true">{{ str_repeat('★', (int) round((float) $provider->rating)) }}</i><span class="nvm-num">{{ number_format((float) $provider->rating, 1) }} ({{ number_format((int) $provider->total_reviews) }} รีวิว)</span></span>
                        @endif
                        @if($provider->bio)<p>{{ $provider->bio }}</p>@endif
                    </div>
                </div>
                @if($trackable && $provider->phone)
                    <div class="nvm-os-acts nvm-gap-t">
                        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $provider->phone) }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>โทรติดต่อ</a>
                    </div>
                @endif
            </section>
        @endif

        {{-- ── ไทม์ไลน์สถานะ ── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-tl-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-os-bs-tl-h">สถานะการให้บริการ</h2></div></div>
            <ol class="nvm-os-tl">
                @foreach($flow as $key => [$label, $icon, $at])
                    @php
                        $idx = array_search($key, $order, true);
                        $done = $cancelled ? (bool) $at && $key !== 'completed' : $idx <= $current;
                        $now = ! $cancelled && $idx === $current && $bk->status !== 'completed';
                    @endphp
                    <li class="{{ $done && ! $now ? 'is-done' : '' }} {{ $now ? 'is-now' : '' }}">
                        <span class="nvm-os-tl__dot"><svg class="nvm-ico"><use href="#nvm-i-{{ $done && ! $now ? 'check' : $icon }}"/></svg></span>
                        <div class="nvm-os-tl__b">
                            <b>{{ $label }}</b>
                            @if($now)<small>สถานะปัจจุบัน</small>@endif
                            @if($done && $at)<small class="nvm-num">{{ $fmt($at) }}</small>@endif
                        </div>
                    </li>
                @endforeach
                @if($cancelled)
                    <li class="is-bad">
                        <span class="nvm-os-tl__dot"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
                        <div class="nvm-os-tl__b">
                            <b>{{ $bk->status === 'provider_rejected' ? 'ผู้ให้บริการปฏิเสธงาน' : 'ยกเลิกแล้ว' }}</b>
                            @if($bk->cancellation_reason)<small>เหตุผล: {{ $bk->cancellation_reason }}</small>@endif
                            @if($bk->status === 'provider_rejected' && $bk->provider_rejected_reason)<small>เหตุผล: {{ $bk->provider_rejected_reason }}</small>@endif
                            @if($bk->cancelled_at)<small class="nvm-num">{{ $fmt($bk->cancelled_at) }}</small>@endif
                        </div>
                    </li>
                @endif
            </ol>
        </section>

        {{-- ── สถานที่ให้บริการ ── --}}
        @if($serviceLocation)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-loc-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Location</small><h2 id="nvm-os-bs-loc-h">สถานที่ให้บริการ</h2></div></div>
                <ul class="nvm-os-lines">
                    <li><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span>{{ $serviceLocation->address }}</span></li>
                    @if($serviceLocation->building_name)
                        <li><svg class="nvm-ico"><use href="#nvm-i-home"/></svg><span>{{ $serviceLocation->building_name }}@if($serviceLocation->floor_number) ชั้น {{ $serviceLocation->floor_number }}@endif @if($serviceLocation->room_number) ห้อง {{ $serviceLocation->room_number }}@endif</span></li>
                    @endif
                    @if($serviceLocation->landmark)
                        <li><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>จุดสังเกต: {{ $serviceLocation->landmark }}</span></li>
                    @endif
                    <li><svg class="nvm-ico"><use href="#nvm-i-user"/></svg><span>{{ $serviceLocation->contact_name ?: $bk->user?->name }}</span></li>
                    @if($serviceLocation->contact_phone)
                        <li><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span class="nvm-num">{{ $serviceLocation->contact_phone }}</span></li>
                    @endif
                </ul>
                {{-- 🐛 เดิมรอ Google Maps ที่ไม่เคยถูกโหลด กล่องแผนที่เลยว่างเปล่า → ใช้ Leaflet ชุดเดียวกับหน้าคำสั่งซื้อ --}}
                @if($serviceLocation->latitude && $serviceLocation->longitude)
                    <div class="nvm-os-map nvm-os-map--sm nvm-gap-t" role="img" aria-label="แผนที่สถานที่ให้บริการ"
                         x-data x-init="$nextTick(() => { if (window.nvmOsMap && window.nvmOsMap.ready()) { const m = window.nvmOsMap.create($el, {{ (float) $serviceLocation->latitude }}, {{ (float) $serviceLocation->longitude }}, 15); window.nvmOsMap.pin(m, {{ (float) $serviceLocation->latitude }}, {{ (float) $serviceLocation->longitude }}, 'home'); } })"></div>
                @endif
            </section>
        @endif

        {{-- ── ให้คะแนนบริการ (เสร็จสิ้นแล้ว + ยังไม่เคยให้) ── --}}
        @if($canReview)
            <section class="nvm-card nvm-box" id="review" aria-labelledby="nvm-os-bs-rv-h" x-data="{ rating: {{ (int) old('rating', 0) }}, busy: false }">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Rate the service</small><h2 id="nvm-os-bs-rv-h">ให้คะแนนบริการ</h2></div></div>
                <form method="POST" action="{{ route('user.service-reviews.store', $bk) }}" class="nvm-form" @submit="if (busy || rating < 1) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <div class="nvm-field">
                        <span class="nvm-label" id="bk-rating-lbl">คะแนน <em>*</em></span>
                        <div class="nvm-os-stars" role="radiogroup" aria-labelledby="bk-rating-lbl">
                            <template x-for="star in 5" :key="star">
                                <button type="button" role="radio" @click="rating = star" :aria-label="star + ' ดาว'" :aria-checked="(rating === star).toString()" :class="star <= rating && 'is-on'">★</button>
                            </template>
                            <span x-show="rating > 0" x-cloak class="nvm-num" x-text="rating + ' ดาว'"></span>
                        </div>
                        <input type="hidden" name="rating" :value="rating">
                        @error('rating')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="bk-review">ความคิดเห็น (ไม่บังคับ)</label>
                        <textarea id="bk-review" name="review" rows="3" maxlength="1000" class="nvm-input @error('review') is-bad @enderror" placeholder="เล่าประสบการณ์การใช้บริการ">{{ old('review') }}</textarea>
                        @error('review')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || rating < 1"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ส่งคะแนน</button>
                </form>
            </section>
        @elseif($bk->rating)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-rvd-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Your rating</small><h2 id="nvm-os-bs-rvd-h">คะแนนที่คุณให้</h2></div></div>
                <span class="nvm-os-rate"><i aria-hidden="true">{{ str_repeat('★', (int) $bk->rating) }}</i><span class="nvm-num">{{ (int) $bk->rating }}/5</span></span>
                @if($bk->review)<p class="nvm-desc nvm-gap-t">{{ $bk->review }}</p>@endif
            </section>
        @endif
    </div>

    <aside class="nvm-stack">
        {{-- ── รายละเอียดราคา ── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-pr-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Price</small><h2 id="nvm-os-bs-pr-h">รายละเอียดราคา</h2></div></div>
            <dl class="nvm-kv">
                <dt>ค่าบริการ</dt><dd class="nvm-num">฿{{ number_format((float) $bk->base_price, 2) }}</dd>
                @if((float) $bk->distance_price > 0)
                    <dt>ค่าเดินทาง (<span class="nvm-num">{{ (float) $bk->distance_km }}</span> กม.)</dt><dd class="nvm-num">฿{{ number_format((float) $bk->distance_price, 2) }}</dd>
                @endif
                @if((float) $bk->options_price > 0)
                    <dt>ออฟชั่นเพิ่มเติม</dt><dd class="nvm-num">฿{{ number_format((float) $bk->options_price, 2) }}</dd>
                @endif
                <dt>ยอดรวม</dt><dd class="nvm-num">฿{{ number_format((float) $bk->subtotal, 2) }}</dd>
                @if((float) $bk->tax_amount > 0)
                    <dt>ภาษี</dt><dd class="nvm-num">฿{{ number_format((float) $bk->tax_amount, 2) }}</dd>
                @endif
                @if((float) $bk->discount_amount > 0)
                    <dt>ส่วนลด</dt><dd class="nvm-num">-฿{{ number_format((float) $bk->discount_amount, 2) }}</dd>
                @endif
                <dt class="nvm-kv__total">ราคารวมทั้งหมด</dt><dd class="nvm-kv__total nvm-num">฿{{ number_format((float) $bk->total_amount, 2) }}</dd>
            </dl>
        </section>

        @if($bk->items->count())
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-it-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Items</small><h2 id="nvm-os-bs-it-h">รายการ</h2></div></div>
                <ul class="nvm-txs">
                    @foreach($bk->items as $item)
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                            <div class="nvm-tx__t nvm-tx__t--wrap"><b>{{ $item->name }}@if((int) $item->quantity > 1) <span class="nvm-num">× {{ (int) $item->quantity }}</span>@endif</b>@if($item->description)<small>{{ $item->description }}</small>@endif</div>
                            <div class="nvm-tx__r"><span class="nvm-tx__amt nvm-num">฿{{ number_format((float) $item->subtotal, 2) }}</span></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($bk->customer_notes)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-nt-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Notes</small><h2 id="nvm-os-bs-nt-h">หมายเหตุ</h2></div></div>
                <p class="nvm-desc">{{ $bk->customer_notes }}</p>
            </section>
        @endif

        @if($bk->service && (float) $bk->service->pv_value > 0)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-pv2-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Point value</small><h2 id="nvm-os-bs-pv2-h">PV ของบริการนี้</h2></div></div>
                <div class="nvm-minis"><div class="nvm-mini nvm-mini--wide"><small>Point Value</small><b class="nvm-num">{{ number_format((float) $bk->service->pv_value) }} PV</b></div></div>
            </section>
        @endif

        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bs-hp-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Help</small><h2 id="nvm-os-bs-hp-h">ต้องการความช่วยเหลือ?</h2></div></div>
            <p class="nvm-desc">มีปัญหาหรือข้อสงสัยเกี่ยวกับการจองนี้ แจ้งทีมงานผ่านตั๋วช่วยเหลือได้เลย</p>
            {{-- 🐛 เดิมเป็นลิงก์ "#" ที่ไม่พาไปไหน --}}
            @if(RouteFacade::has('user.tickets.create'))
                <a class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ติดต่อฝ่ายสนับสนุน</a>
            @endif
        </section>
    </aside>
</div>

{{-- หน้าต่างยืนยันยกเลิก --}}
@if($bk->canCancel())
    <div class="nvm-modal nvm-os-modal" x-show="cancelOpen" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-os-bs-cc-h" @click.self="cancelOpen = false">
        <div class="nvm-card nvm-modal__box">
            <h2 id="nvm-os-bs-cc-h"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยกเลิกการจองนี้?</h2>
            <p class="nvm-desc nvm-gap-t">การยกเลิกย้อนกลับไม่ได้ ถ้าต้องการเปลี่ยนวันนัด ให้ยกเลิกแล้วจองใหม่</p>
            @if($bk->payment_status === 'paid')
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ชำระเงินแล้ว — ระบบคืนเงินเข้ากระเป๋าเงินของคุณอัตโนมัติ</p>
            @endif
            <form method="POST" action="{{ route('user.bookings.cancel', $bk) }}" class="nvm-form" @submit="if (busy || reason.trim() === '') { $event.preventDefault(); return; } busy = true">
                @csrf
                <div class="nvm-field">
                    {{-- 🐛 เดิมเขียนว่า "ไม่บังคับ" แต่คอนโทรลเลอร์บังคับ reason — เว้นว่างแล้วกดยกเลิกไม่สำเร็จ --}}
                    <label class="nvm-label" for="bs-cancel-reason">เหตุผลในการยกเลิก <em>*</em></label>
                    <textarea id="bs-cancel-reason" name="reason" rows="3" maxlength="500" required class="nvm-input @error('reason') is-bad @enderror" x-model="reason" placeholder="เช่น เปลี่ยนวันนัด ไม่สะดวกแล้ว"></textarea>
                    @error('reason')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-filter__btns">
                    <button type="button" class="nvm-btn nvm-btn--line" @click="cancelOpen = false">ปิด</button>
                    <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy || reason.trim() === ''"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยืนยันยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
@endif

</div>
@endsection
