{{--
    แชทกับลูกค้า — บทสนทนาทั้งหมด (ธีม V4)
--}}
@extends('layouts.seller-v4')

@section('title', 'แชทกับลูกค้า')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="แชทกับลูกค้า" subtitle="ข้อความจากลูกค้าในแต่ละคำสั่งซื้อ ตอบเร็วช่วยให้ขายได้มากขึ้น" icon="💬" />

    <div class="sv4-stats">
        <x-seller-v4.stat label="บทสนทนาทั้งหมด" :value="number_format($stats['total_conversations'] ?? 0)" icon="💬" />
        <x-seller-v4.stat label="รอตอบ" :value="number_format($stats['unread_conversations'] ?? 0)" icon="🔔" :color="\App\Support\Seller\SellerUi::BAD"
                          :href="route('seller.messages.unread')" />
        <x-seller-v4.stat label="ข้อความวันนี้" :value="number_format($stats['messages_today'] ?? 0)" icon="📅" :color="\App\Support\Seller\SellerUi::INFO" />
    </div>

    <nav class="sv4-tabs" aria-label="กรองข้อความ">
        <a href="{{ route('seller.messages.index') }}" class="sv4-tab {{ request('filter') !== 'unread' ? 'on' : '' }}">📩 ทั้งหมด</a>
        <a href="{{ route('seller.messages.unread') }}" class="sv4-tab">
            🔔 ยังไม่อ่าน
            @if(($stats['unread_conversations'] ?? 0) > 0)
                <span class="sv4-count" style="background:{{ \App\Support\Seller\SellerUi::BAD }}; color:var(--tp-on-accent, #fff);">{{ $stats['unread_conversations'] }}</span>
            @endif
        </a>
    </nav>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @include('seller.messages.partials.list', [
            'conversations' => $conversations,
            'emptyTitle' => 'ยังไม่มีข้อความจากลูกค้า',
            'emptyText' => 'เมื่อลูกค้าส่งข้อความเกี่ยวกับคำสั่งซื้อ จะแสดงที่นี่',
        ])
    </div>
</div>
@endsection
