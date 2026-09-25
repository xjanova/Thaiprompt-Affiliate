{{--
 | ส่วนท้ายสาธารณะ ธีม V4 (ใช้คู่กับ x-theme-v4.public-header)
 |
 | ตัวอย่าง:  <x-theme-v4.public-footer />
 |           <x-theme-v4.public-footer :shop-links="[['href' => '#products', 'label' => 'สินค้าแนะนำ']]" />
 |
 | props:
 |   shopLinks  array|null  ลิงก์คอลัมน์ "ร้านค้า & บริการ" [['href' => ..., 'label' => ...]] (null = ชุดมาตรฐาน)
 --}}
@props([
    'shopLinks' => null,
])

@php
    $pfRoute = fn (string $name, $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));

    $pfUser = auth()->user();
    $pfIsAdmin = $pfUser && ((($pfUser->role ?? null) === 'admin') || ($pfUser->is_super_admin ?? false));
    $pfDashUrl = $pfIsAdmin ? $pfRoute('admin.dashboard') : $pfRoute('user.dashboard');
    $pfLoginUrl = $pfRoute('login', [], url('/login'));

    $pfShopLinks = is_array($shopLinks) ? $shopLinks : [
        ['href' => $pfRoute('storefront.index', [], url('/storefront')), 'label' => 'ร้านค้าออนไลน์'],
        ['href' => $pfRoute('taladsod.home', [], url('/taladsod')), 'label' => 'ตลาดสด ใกล้บ้าน'],
        ['href' => $pfRoute('official-shop.index', [], url('/official-shop')), 'label' => 'ร้านค้าทางการ'],
        ['href' => $pfUser ? $pfRoute('user.rider.dashboard') : $pfRoute('taladsod.landing.rider'), 'label' => 'สมัครเป็นไรเดอร์'],
        ['href' => $pfRoute('user.seller-apply.index', [], $pfLoginUrl), 'label' => 'เปิดร้านค้าออนไลน์'],
    ];
@endphp

@once
@push('styles')
<style>
    .tp-foot-link { color: var(--ink2); text-decoration: none; font-size: 12.5px; transition: color .15s ease; }
    .tp-foot-link:hover { color: var(--deep1); }
    .tp-foot-h { font-weight: 700; font-size: 13px; color: var(--ink); margin-bottom: 12px; }
    @media (max-width: 760px) { .tp-foot-grid { grid-template-columns: 1fr 1fr !important; } }
    @media (max-width: 460px) { .tp-foot-grid { grid-template-columns: 1fr !important; } }
</style>
@endpush
@endonce

<footer style="border-top:var(--card-border); margin-top:auto;">
    <div style="max-width:1180px; margin:0 auto; padding:42px clamp(16px,3vw,40px) 26px; display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:28px;" class="tp-foot-grid">
        {{-- แบรนด์ --}}
        <div>
            <x-theme-v4.brand-logo :height="40" />
            <p style="font-size:12.5px; color:var(--ink2); line-height:1.7; margin:14px 0 0; max-width:280px;">แพลตฟอร์มคนไทย รวมอีคอมเมิร์ซ ไรเดอร์ ตลาดสด กระเป๋าเงินดิจิทัล และระบบปันผลโปร่งใสด้วย Blockchain ของเราเอง</p>
        </div>
        {{-- ร้านค้า & บริการ --}}
        <div>
            <div class="tp-foot-h">ร้านค้า &amp; บริการ</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($pfShopLinks as $link)
                    <a href="{{ $link['href'] }}" class="tp-foot-link">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>
        {{-- บริษัท --}}
        <div>
            <div class="tp-foot-h">บริษัท</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="{{ $pfRoute('page.show', 'about-us') }}" class="tp-foot-link">เกี่ยวกับเรา</a>
                <a href="{{ $pfRoute('page.show', 'contact') }}" class="tp-foot-link">ติดต่อเรา</a>
                <a href="{{ $pfRoute('page.show', 'faq') }}" class="tp-foot-link">คำถามที่พบบ่อย</a>
                <a href="{{ $pfRoute('wiki.index') }}" class="tp-foot-link">วิกิแพลตฟอร์ม</a>
            </div>
        </div>
        {{-- ข้อกำหนด & นโยบาย --}}
        <div>
            <div class="tp-foot-h">ข้อกำหนด &amp; นโยบาย</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="{{ $pfRoute('terms-of-service.html') }}" class="tp-foot-link">ข้อกำหนดการใช้งาน</a>
                <a href="{{ $pfRoute('privacy-policy.html') }}" class="tp-foot-link">นโยบายความเป็นส่วนตัว</a>
                <a href="{{ $pfRoute('cookie-policy') }}" class="tp-foot-link">นโยบายคุกกี้</a>
                <a href="{{ $pfRoute('software-warranty') }}" class="tp-foot-link">การรับประกันซอฟต์แวร์</a>
            </div>
        </div>
    </div>
    <div style="border-top:var(--card-border); padding:16px clamp(16px,3vw,40px); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <div style="font-size:12px; color:var(--ink2); line-height:1.7;">© {{ date('Y') + 543 }} ไทยพร๊อมท์ · ThaiPrompt — แพลตฟอร์มคนไทย เพื่อคนไทย เพื่อเอเชีย<br>พัฒนาและรับประกันโดย <a href="https://xman4289.com" target="_blank" rel="noopener noreferrer" style="color:var(--deep1); text-decoration:none;">บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด</a></div>
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <a href="{{ $pfRoute('privacy-policy.html') }}" class="tp-foot-link">ความเป็นส่วนตัว</a>
            <a href="{{ $pfRoute('terms-of-service.html') }}" class="tp-foot-link">ข้อกำหนด</a>
            <a href="{{ $pfUser ? $pfDashUrl : $pfLoginUrl }}" class="tp-foot-link">{{ $pfUser ? 'แดชบอร์ด' : 'เข้าสู่ระบบ' }}</a>
        </div>
    </div>
</footer>
