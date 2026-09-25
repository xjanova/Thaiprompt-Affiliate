@extends('layouts.seller-v4')

@section('title', 'คะแนนร้านค้า')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="คะแนนและรีวิวร้าน" icon="⭐" crumb="ร้านค้า · การตลาด"
                         subtitle="รีวิวจากลูกค้าที่ซื้อจริง — ตอบกลับทุกรีวิวช่วยให้ร้านน่าเชื่อถือและได้คะแนน Official Shop เพิ่ม">
        <a href="{{ route('seller.store-rating.statistics') }}" class="tp-btn tp-btn-sm">📈 สถิติ</a>
        <a href="{{ route('seller.store-rating.all') }}" class="tp-btn tp-btn-sm">ดูรีวิวทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    @include('seller.store-rating.partials.summary', ['stats' => $stats])

    @if($pendingResponse > 0)
        <a href="{{ route('seller.store-rating.all', ['responded' => '0']) }}" class="tp-card tp-card-hover" style="border-left:4px solid var(--tp-warn, #e0a52e); text-decoration:none; color:var(--ink); display:flex; justify-content:space-between; align-items:center; gap:10px;">
            <span style="font-weight:700;">💬 มีรีวิวที่ยังไม่ได้ตอบ {{ number_format((int) $pendingResponse) }} รายการ</span>
            <span style="color:var(--deep1); font-weight:700;">ตอบเลย →</span>
        </a>
    @endif

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div class="tp-section-h">🕘 รีวิวล่าสุด</div>
        @if($recentRatings->count() > 0)
            <a href="{{ route('seller.store-rating.all') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ทั้งหมด →</a>
        @endif
    </div>

    @forelse($recentRatings as $rating)
        @include('seller.store-rating.partials.review-card', ['rating' => $rating])
    @empty
        <div class="tp-card">
            <x-seller-kit.empty icon="⭐" title="ยังไม่มีรีวิว" text="เมื่อลูกค้าได้รับสินค้าและให้คะแนนร้าน รีวิวจะแสดงที่นี่" />
        </div>
    @endforelse
</div>
@endsection
