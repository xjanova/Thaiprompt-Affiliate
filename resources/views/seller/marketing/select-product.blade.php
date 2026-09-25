@extends('layouts.seller-v4')

@section('title', 'เลือกสินค้าโปรโมท')

@php
    $canPromote = (bool) ($promoCheck['can_promote'] ?? false);
    $availableAt = $promoCheck['available_at'] ?? null;
    $criteriaNames = ['rating' => 'คะแนนรีวิว', 'reviews' => 'จำนวนรีวิว', 'sales' => 'ยอดขาย', 'followers' => 'ผู้ติดตามร้าน', 'freshness' => 'ความใหม่', 'stock' => 'สต็อก', 'images' => 'รูปสินค้า'];
@endphp

@section('content')
<div x-data="scorePreview(@js($criteriaNames))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="เลือกสินค้าโปรโมท" icon="🚀" crumb="ร้านค้า · การตลาด"
                         subtitle="ดันสินค้าใหม่ขึ้นหน้า Official Shop ฟรี {{ \App\Models\NewProductPromotion::PROMOTION_DAYS }} วัน (ใช้ได้ครั้งละ 1 สินค้า และสินค้าละ 1 ครั้ง)" />

    @include('seller.marketing.partials.nav')

    @unless($canPromote)
        <div class="tp-card" style="border-left:4px solid var(--tp-warn, #e0a52e);">
            <div style="font-weight:800;">⏳ {{ $promoCheck['reason'] ?? 'ยังใช้สิทธิ์โปรโมทไม่ได้ตอนนี้' }}</div>
            @if($availableAt)
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ใช้สิทธิ์ได้อีกครั้ง {{ \Illuminate\Support\Carbon::parse($availableAt)->format('d/m/Y H:i') }} ({{ \Illuminate\Support\Carbon::parse($availableAt)->diffForHumans() }})</div>
            @endif
        </div>
    @endunless

    @if($products->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px;">
            @foreach($products as $product)
                <div class="tp-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <div class="tp-inset-sm" style="aspect-ratio:4/3; display:grid; place-items:center; overflow:hidden; border-radius:0; font-size:40px;">
                        @if($product->main_image_url)
                            <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <span aria-hidden="true">📦</span>
                        @endif
                    </div>
                    <div style="padding:14px 16px; display:flex; flex-direction:column; gap:8px; flex:1;">
                        <div style="font-weight:800; font-size:14px; overflow-wrap:anywhere;">{{ $product->name }}</div>
                        <div class="tp-num" style="font-weight:800; color:var(--deep1);">฿{{ number_format((float) $product->price, 2) }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">ลงขายเมื่อ {{ optional($product->created_at)->diffForHumans() }}</div>
                        <div style="display:flex; gap:8px; margin-top:auto; flex-wrap:wrap;">
                            <button type="button" class="tp-btn tp-btn-sm" style="flex:1;" @click="open(@js(route('seller.marketing.preview-score', $product)), @js($product->name))">📊 คะแนน AI</button>
                            <form method="POST" action="{{ route('seller.marketing.promote', $product) }}" style="flex:1;"
                                  onsubmit="return confirm('ใช้สิทธิ์โปรโมทสินค้านี้ {{ \App\Models\NewProductPromotion::PROMOTION_DAYS }} วัน? ระหว่างโปรโมทจะแก้ไขสินค้าไม่ได้ และสินค้าชิ้นนี้ใช้สิทธิ์ได้ครั้งเดียว');">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="width:100%; {{ $canPromote ? '' : 'opacity:.5; cursor:not-allowed;' }}" @disabled(! $canPromote)>🚀 โปรโมท</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($products->hasPages())
            <div>{{ $products->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="📦" title="ไม่มีสินค้าที่ใช้สิทธิ์ได้" text="สินค้าที่เปิดขายและยังไม่เคยโปรโมทจะแสดงที่นี่">
                <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">➕ เพิ่มสินค้าใหม่</a>
            </x-seller-kit.empty>
        </div>
    @endif

    {{-- หน้าต่างคะแนน AI ของสินค้า --}}
    <div x-show="show" x-cloak x-transition.opacity @keydown.escape.window="show = false">
      <div style="position:fixed; inset:0; z-index:90; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding:16px;"
           @click.self="show = false" role="dialog" aria-modal="true" aria-labelledby="score-title">
        <div class="tp-card" style="width:100%; max-width:480px; max-height:90vh; overflow-y:auto; padding:22px; background:var(--surf); display:flex; flex-direction:column; gap:12px;">
            <div id="score-title" style="font-size:17px; font-weight:800;" x-text="'📊 คะแนน AI · ' + name"></div>
            <template x-if="loading"><div style="color:var(--ink2); font-size:13px;"><i class="fas fa-spinner fa-spin"></i> กำลังคำนวณ…</div></template>
            <template x-if="error"><div style="color:var(--tp-bad, #d9534f); font-size:13px;" x-text="error"></div></template>
            <template x-if="data">
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:baseline; gap:10px;">
                        <span class="tp-num" style="font-size:34px; font-weight:800; color:var(--deep1);" x-text="Number(data.score).toFixed(1)"></span>
                        <span style="color:var(--ink2); font-size:13px;" x-text="'/ ต้องการ ' + data.min_required"></span>
                    </div>
                    <div class="tp-inset-sm" style="height:10px; border-radius:99px; overflow:hidden;">
                        <div :style="'height:100%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); width:' + Math.min(100, Math.max(2, data.score)) + '%'"></div>
                    </div>
                    <template x-for="(v, k) in (data.breakdown || {})" :key="k">
                        <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                            <span x-text="label(k)"></span>
                            <span class="tp-num" style="font-weight:700;" x-text="Number((v && v.score) ?? v ?? 0).toFixed(0)"></span>
                        </div>
                    </template>
                    <template x-for="(tip, i) in (data.tips || [])" :key="i">
                        <div class="tp-inset-sm" style="border-radius:12px; padding:9px 12px; font-size:12.5px; line-height:1.6;" x-text="'💡 ' + tip.message"></div>
                    </template>
                </div>
            </template>
            <button type="button" class="tp-btn" @click="show = false">ปิด</button>
        </div>
      </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ดูคะแนน AI ของสินค้าก่อนตัดสินใจโปรโมท (เรียก JSON seller.marketing.preview-score)
    function scorePreview(names) {
        return {
            show: false, loading: false, error: '', data: null, name: '',
            label(k) { return names[k] || k; },
            async open(url, name) {
                this.name = name; this.data = null; this.error = ''; this.loading = true; this.show = true;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) { this.data = json; } else { this.error = json.message || 'คำนวณคะแนนไม่สำเร็จ'; }
                } catch (e) {
                    this.error = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endpush
