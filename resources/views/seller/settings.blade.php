@extends('layouts.seller-v4')

@section('title', 'ตั้งค่า')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    // การ์ดทางลัด: [ไอคอน, ชื่อ, คำอธิบาย, route]
    $shortcuts = [
        ['🏪', 'ตั้งค่าร้านค้า', 'ชื่อร้าน โลโก้ ที่อยู่ ภาษี และการส่งด้วยไรเดอร์', 'seller.store.settings'],
        ['🎨', 'ปรับแต่งหน้าร้าน', 'สีธีม แบนเนอร์สไลด์ สินค้าแนะนำ SEO', 'seller.store.layout.index'],
        ['💡', 'วางแผนราคา & กลยุทธ์', 'ตั้งราคาให้มีกำไรพร้อมคำแนะนำ', 'seller.pricing.planner'],
        ['👤', 'โปรไฟล์', 'ข้อมูลส่วนตัวและรหัสผ่าน', 'seller.profile'],
        ['💎', 'แพ็กเกจร้านค้า', 'ดู/เปลี่ยนแพ็กเกจของร้าน', 'seller.packages'],
        ['👛', 'กระเป๋าเงิน', 'ยอดเงิน รายได้รอโอน และถอนเงิน', 'seller.wallet.index'],
        ['🖥️', 'ตั้งค่า POS', 'ระบบขายหน้าร้าน', 'seller.pos.settings'],
        ['📈', 'ตั้งค่ารายงาน', 'การเก็บข้อมูลวิเคราะห์ร้าน', 'seller.analytics.settings'],
    ];
    $storeStatusLabels = ['active' => 'เปิดใช้งาน', 'pending' => 'รออนุมัติ', 'suspended' => 'ถูกระงับ', 'closed' => 'ปิดร้าน', 'rejected' => 'ไม่ผ่านการอนุมัติ'];
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="ตั้งค่า" subtitle="จัดการร้านค้า บัญชี และระบบต่างๆ ของคุณ" icon="⚙️" />

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:14px;">
        @foreach($shortcuts as [$icon, $title, $desc, $routeName])
            @if(\Illuminate\Support\Facades\Route::has($routeName))
                <a href="{{ route($routeName) }}" class="tp-card tp-card-hover" style="padding:18px; display:flex; gap:13px; align-items:flex-start; text-decoration:none; color:var(--ink);">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:21px;">{{ $icon }}</span>
                    <span style="min-width:0;">
                        <span style="display:block; font-weight:800; font-size:14px;">{{ $title }}</span>
                        <span style="display:block; font-size:12px; color:var(--ink2); margin-top:3px; line-height:1.5;">{{ $desc }}</span>
                        @if($routeName === 'seller.packages' && $store && $store->package)
                            <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::OK) }} margin-top:6px;">ปัจจุบัน: {{ $store->package->display_name ?? $store->package->name }}</span>
                        @endif
                    </span>
                </a>
            @endif
        @endforeach
    </div>

    <div class="tp-card" style="padding:20px;">
        <div class="sv4-h2">🧾 ข้อมูลบัญชี</div>
        <div style="margin-top:10px;">
            <div class="sv4-kv"><span>ชื่อ</span><span>{{ $user->name }}</span></div>
            <div class="sv4-kv"><span>อีเมล</span><span style="font-weight:500;">{{ $user->email }}</span></div>
            @if($store)
                <div class="sv4-kv"><span>ชื่อร้าน</span><span>{{ $store->store_name }}</span></div>
                <div class="sv4-kv"><span>สถานะร้าน</span>
                    <span><span class="sv4-pill" style="{{ SellerUi::pill($store->status === 'active' ? SellerUi::OK : SellerUi::WARN) }}">{{ $storeStatusLabels[$store->status] ?? $store->status }}</span></span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
