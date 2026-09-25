@extends('layouts.seller-v4')

@section('title', 'รีวิวทั้งหมด')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="รีวิวทั้งหมด" icon="⭐" crumb="ร้านค้า · การตลาด · คะแนนร้าน">
        <a href="{{ route('seller.store-rating.index') }}" class="tp-btn tp-btn-sm">← สรุปคะแนน</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <form method="GET" action="{{ route('seller.store-rating.all') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="r-rating" style="font-size:12px; font-weight:700; color:var(--ink2);">คะแนน</label>
            <select id="r-rating" name="rating" class="tp-input" style="margin-top:6px;">
                <option value="">ทุกคะแนน</option>
                @for($s = 5; $s >= 1; $s--)
                    <option value="{{ $s }}" @selected((string) request('rating') === (string) $s)>{{ str_repeat('★', $s) }} ({{ $s }})</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="r-resp" style="font-size:12px; font-weight:700; color:var(--ink2);">การตอบกลับ</label>
            <select id="r-resp" name="responded" class="tp-input" style="margin-top:6px;">
                <option value="">ทั้งหมด</option>
                <option value="0" @selected(request('responded') === '0')>ยังไม่ตอบ</option>
                <option value="1" @selected(request('responded') === '1')>ตอบแล้ว</option>
            </select>
        </div>
        <div>
            <label for="r-sort" style="font-size:12px; font-weight:700; color:var(--ink2);">เรียงตาม</label>
            <select id="r-sort" name="sort" class="tp-input" style="margin-top:6px;">
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>ล่าสุด</option>
                <option value="helpful" @selected(request('sort') === 'helpful')>มีประโยชน์มากสุด</option>
                <option value="highest" @selected(request('sort') === 'highest')>คะแนนสูง → ต่ำ</option>
                <option value="lowest" @selected(request('sort') === 'lowest')>คะแนนต่ำ → สูง</option>
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 กรอง</button>
            <a href="{{ route('seller.store-rating.all') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    @forelse($ratings as $rating)
        @include('seller.store-rating.partials.review-card', ['rating' => $rating])
    @empty
        <div class="tp-card">
            <x-seller-kit.empty icon="⭐" title="ไม่พบรีวิว" text="ยังไม่มีรีวิว หรือลองเปลี่ยนตัวกรอง" />
        </div>
    @endforelse

    @if($ratings->hasPages())
        <div>{{ $ratings->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
