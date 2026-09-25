@extends('layouts.seller-v4')

@section('title', 'รีวิว #'.$rating->id)

@php
    $subs = [
        ['🛎️ บริการ', $rating->service_rating],
        ['🚚 การจัดส่ง', $rating->shipping_rating],
        ['💬 การสื่อสาร', $rating->communication_rating],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="'รีวิว #'.$rating->id" icon="⭐" crumb="ร้านค้า · การตลาด · คะแนนร้าน"
                         :subtitle="'จาก '.($rating->reviewer_name ?? 'ลูกค้า').' · '.optional($rating->created_at)->format('d/m/Y H:i')">
        <a href="{{ route('seller.store-rating.all') }}" class="tp-btn tp-btn-sm">← รีวิวทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <x-seller-kit.errors />

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        <div style="flex:2 1 380px; min-width:0;">
            @include('seller.store-rating.partials.review-card', ['rating' => $rating, 'showLink' => false])
        </div>
        <div style="flex:1 1 260px; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
                <div class="tp-section-h">คะแนนแยกด้าน</div>
                @foreach($subs as [$sLabel, $sValue])
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span>{{ $sLabel }}</span>
                        <span style="color:var(--accent1); letter-spacing:1px;">{{ $sValue ? str_repeat('★', (int) $sValue).str_repeat('☆', max(0, 5 - (int) $sValue)) : '—' }}</span>
                    </div>
                @endforeach
            </div>
            @if($rating->order)
                <div class="tp-card" style="display:flex; flex-direction:column; gap:8px;">
                    <div class="tp-section-h">🧾 ออเดอร์ที่รีวิว</div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เลขที่</span><span class="tp-num" style="font-weight:700;">{{ $rating->order->order_number ?? '#'.$rating->order->id }}</span></div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">วันที่สั่ง</span><span class="tp-num">{{ optional($rating->order->created_at)->format('d/m/Y') }}</span></div>
                    @if(\Illuminate\Support\Facades\Route::has('seller.orders.show'))
                        <a href="{{ route('seller.orders.show', $rating->order->id) }}" class="tp-btn tp-btn-sm">ดูออเดอร์ →</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
