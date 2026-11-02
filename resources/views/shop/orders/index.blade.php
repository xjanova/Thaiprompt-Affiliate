{{--
    คำสั่งซื้อของฉัน (ร้านค้าออนไลน์) — ธีมโนวาเต็ม
    ข้อมูลจาก OrderController::index: $orders (paginator + items.product), $statusCounts [all, pending, processing, shipped, completed]
    ตัวกรอง ?status= · ชำระใหม่ POST orders.retry-payment · ยืนยันรับของ POST orders.confirm-received · ยกเลิก (ต้องระบุเหตุผล) ที่หน้ารายละเอียด
--}}
@extends('layouts.user-nova')

@section('title', 'คำสั่งซื้อของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $oiStatus = is_scalar(request('status')) ? (string) request('status') : '';
    $oiTabs = [
        '' => ['ทั้งหมด', $statusCounts['all'] ?? 0],
        'pending' => ['รอชำระ', $statusCounts['pending'] ?? 0],
        'processing' => ['กำลังเตรียม', $statusCounts['processing'] ?? 0],
        'shipped' => ['กำลังจัดส่ง', $statusCounts['shipped'] ?? 0],
        'completed' => ['สำเร็จ', $statusCounts['completed'] ?? 0],
    ];
    // โทนชิปสถานะคำสั่งซื้อ
    $oiTone = fn (string $status) => match ($status) {
        'pending' => 'warn',
        'paid', 'processing', 'confirmed' => 'info',
        'shipped', 'in_transit', 'out_for_delivery' => 'gold',
        'delivered', 'completed' => 'ok',
        'cancelled', 'refunded' => 'bad',
        default => '',
    };
    $oiFmt = fn ($d) => $d ? $d->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-';
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-os-oi-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My orders</div>
            <h1 id="nvm-os-oi-h">คำสั่งซื้อของฉัน</h1>
            <p class="nvm-ph__lead">ติดตามสถานะ ชำระเงิน และรีวิวสินค้าที่สั่งจากร้านค้าออนไลน์</p>
            <div class="nvm-bal">
                <small>คำสั่งซื้อทั้งหมด</small>
                <b class="nvm-foil nvm-num">{{ number_format((int) ($statusCounts['all'] ?? 0)) }}<span> รายการ</span></b>
            </div>
            <div class="nvm-os-acts nvm-os-acts--ph">
                @if(RouteFacade::has('storefront.index'))
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('storefront.index') }}"><svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>ช้อปต่อ</a>
                @endif
                @if(RouteFacade::has('taladsod.orders'))
                    <a class="nvm-btn nvm-btn--ghost" href="{{ route('taladsod.orders') }}"><svg class="nvm-ico"><use href="#nvm-i-basket"/></svg>คำสั่งซื้อตลาดสด</a>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/cart.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(($statusCounts['pending'] ?? 0) > 0 && $oiStatus !== 'pending')
    <a class="nvm-card nvm-alert" href="{{ route('orders.index', ['status' => 'pending']) }}">
        <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="56" height="56" loading="lazy">
        <div class="nvm-alert__txt">
            <b>มีคำสั่งซื้อรอชำระ {{ number_format((int) $statusCounts['pending']) }} รายการ</b>
            <p>ชำระให้เรียบร้อยเพื่อให้ร้านเริ่มเตรียมสินค้า — แตะเพื่อดูรายการ</p>
        </div>
        <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
    </a>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-os-oil-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Orders</small><h2 id="nvm-os-oil-h">รายการคำสั่งซื้อ</h2></div>
        <nav class="nvm-tabs" aria-label="กรองสถานะคำสั่งซื้อ">
            @foreach($oiTabs as $key => [$label, $count])
                <a href="{{ route('orders.index', $key === '' ? [] : ['status' => $key]) }}" class="{{ $oiStatus === $key ? 'is-on' : '' }}" @if($oiStatus === $key) aria-current="page" @endif>
                    {{ $label }}<span class="nvm-chip nvm-num">{{ number_format((int) $count) }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if($orders->count())
        <div class="nvm-os-list">
            @foreach($orders as $order)
                @php
                    $oiItems = $order->items;
                    $oiCanPay = $order->canRetryPayment() && ! $order->isCod();
                    $oiTn = $oiTone((string) $order->status);
                @endphp
                <article class="nvm-card nvm-os-order" aria-label="คำสั่งซื้อ #{{ $order->order_number }}">
                    <div class="nvm-os-order__head">
                        <div>
                            <div class="nvm-os-order__no">
                                <a class="nvm-num" href="{{ route('orders.show', $order->id) }}">#{{ $order->order_number }}</a>
                                <span class="nvm-chip {{ $oiTn ? 'nvm-chip--'.$oiTn : '' }}">{{ $order->status_label }}</span>
                                @if($order->isRiderDelivery())
                                    <span class="nvm-chip nvm-chip--info"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>ไรเดอร์</span>
                                @endif
                            </div>
                            <div class="nvm-os-order__meta nvm-num">
                                {{ $oiFmt($order->created_at) }} · {{ number_format((int) $order->total_items) }} ชิ้น · {{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}
                            </div>
                        </div>
                        <div class="nvm-os-order__amt">
                            <small>ยอดรวม</small>
                            <b class="nvm-num">฿{{ number_format((float) $order->total_amount, 2) }}</b>
                        </div>
                    </div>

                    <ul class="nvm-os-items">
                        @foreach($oiItems->take(3) as $item)
                            @php $oiImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image); @endphp
                            <li class="nvm-os-item">
                                <span class="nvm-os-thumb">@if($oiImg)<img src="{{ $oiImg }}" alt="" loading="lazy">@else<svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>@endif</span>
                                <span class="nvm-os-item__t">{{ $item->product_name }}</span>
                                <span class="nvm-os-item__q nvm-num">× {{ (int) $item->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($oiItems->count() > 3)
                        <span class="nvm-os-more">และอีก {{ $oiItems->count() - 3 }} รายการ</span>
                    @endif

                    @if($order->tracking_number)
                        <span class="nvm-os-tag"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>เลขพัสดุ <b class="nvm-num">{{ $order->tracking_number }}</b></span>
                    @endif

                    <div class="nvm-os-acts">
                        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('orders.show', $order->id) }}"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูรายละเอียด</a>
                        @if($oiCanPay)
                            <form method="POST" action="{{ route('orders.retry-payment', $order->id) }}" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                                @csrf
                                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>ชำระเงินตอนนี้</button>
                            </form>
                        @endif
                        @if($order->status === 'delivered')
                            <form method="POST" action="{{ route('orders.confirm-received', $order->id) }}" x-data="{ busy: false }"
                                  @submit="if (busy || ! confirm(@js('ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?'))) { $event.preventDefault(); return; } busy = true">
                                @csrf
                                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ได้รับสินค้าแล้ว</button>
                            </form>
                        @endif
                        @if($order->canBeCancelled())
                            <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-os-btn-bad" href="{{ route('orders.show', $order->id) }}#cancel"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิก</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($orders->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $orders->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/icons/cart.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $oiStatus === '' ? 'ยังไม่มีคำสั่งซื้อ' : 'ไม่มีคำสั่งซื้อในสถานะนี้' }}</b>
            {{ $oiStatus === '' ? 'เลือกซื้อสินค้าคุณภาพจากร้านค้าออนไลน์ได้เลย' : 'ลองเลือกสถานะอื่น' }}
            @if($oiStatus === '' && RouteFacade::has('storefront.index'))
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('storefront.index') }}"><svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>เริ่มช้อปปิ้ง</a></div>
            @endif
        </div>
    @endif
</section>
@endsection
