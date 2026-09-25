@extends('layouts.seller-v4')

@section('title', 'ใบปะหน้าพัสดุ')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ใบปะหน้าพัสดุ" icon="📦" crumb="ร้านค้า · POS · ฉลากบาร์โค้ด"
                         subtitle="พิมพ์ที่อยู่ผู้รับจากออเดอร์ออนไลน์ของร้าน" />

    @include('seller.pos.partials.nav')

    <div class="tp-card">
        <x-seller-kit.empty icon="📦" title="พิมพ์ใบปะหน้าได้จากหน้าออเดอร์"
                            text="เปิดออเดอร์ที่รอจัดส่ง แล้วกดปุ่มพิมพ์ใบปะหน้า ระบบจะดึงชื่อ ที่อยู่ และเบอร์โทรผู้รับให้อัตโนมัติ">
            <a href="{{ route('seller.orders.pending-shipping') }}" class="tp-btn tp-btn-primary tp-btn-sm">📋 ออเดอร์รอจัดส่ง</a>
            <a href="{{ route('seller.pos.labels.index') }}" class="tp-btn tp-btn-sm">← หน้าพิมพ์ฉลาก</a>
        </x-seller-kit.empty>
    </div>

    @if($templates->count() > 0)
        <div class="tp-card">
            <div class="tp-section-h">🧩 Template ใบปะหน้าที่พร้อมใช้ ({{ number_format($templates->count()) }})</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                @foreach($templates as $template)
                    <span class="tp-pill tp-pill-soft">{{ $template->name }} · {{ rtrim(rtrim(number_format((float) $template->paper_width, 1), '0'), '.') }}×{{ rtrim(rtrim(number_format((float) $template->paper_height, 1), '0'), '.') }} มม.</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
