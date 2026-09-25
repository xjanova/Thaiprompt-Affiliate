{{--
    แชทกับลูกค้า — เฉพาะบทสนทนาที่ยังไม่อ่าน (ธีม V4)
--}}
@extends('layouts.seller-v4')

@section('title', 'ข้อความยังไม่อ่าน')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="ข้อความยังไม่อ่าน" subtitle="ลูกค้าที่รอคำตอบจากร้านของคุณ" icon="🔔" :back="route('seller.messages.index')" />

    @if(($stats['unread_conversations'] ?? 0) > 0)
        <div class="sv4-note" style="--c:{{ \App\Support\Seller\SellerUi::BAD }}; font-size:13px;">
            🔔 มี <b>{{ number_format($stats['unread_conversations']) }}</b> บทสนทนาที่รอตอบ ({{ number_format($stats['unread_messages'] ?? 0) }} ข้อความ) — ตอบภายใน 1 ชั่วโมงช่วยเพิ่มโอกาสขาย
        </div>
    @endif

    <nav class="sv4-tabs" aria-label="กรองข้อความ">
        <a href="{{ route('seller.messages.index') }}" class="sv4-tab">📩 ทั้งหมด</a>
        <a href="{{ route('seller.messages.unread') }}" class="sv4-tab on">🔔 ยังไม่อ่าน</a>
    </nav>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @include('seller.messages.partials.list', [
            'conversations' => $conversations,
            'emptyTitle' => 'ตอบครบทุกข้อความแล้ว 🎉',
            'emptyText' => 'ไม่มีข้อความที่รอตอบ',
        ])
    </div>
</div>
@endsection
