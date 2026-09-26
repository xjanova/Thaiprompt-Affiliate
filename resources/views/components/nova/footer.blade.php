{{--
 | ท้ายเว็บธีม "โนวา" — ลิงก์ชุดเดียวกับ x-theme-v4.public-footer (ร้านค้า/บริษัท/นโยบาย)
 | ต้องโหลดคู่กับ public/theme-nova/nova.css
 --}}
@php
    $rfRoute = fn (string $name, $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));

    $rfUser = auth()->user();
    $rfIsAdmin = $rfUser && ((($rfUser->role ?? null) === 'admin') || ($rfUser->is_super_admin ?? false));
    $rfDashUrl = $rfIsAdmin ? $rfRoute('admin.dashboard') : $rfRoute('user.dashboard');
    $rfLoginUrl = $rfRoute('login', [], url('/login'));

    $rfShop = [
        ['href' => $rfRoute('storefront.index', [], url('/storefront')), 'label' => 'ร้านค้าออนไลน์'],
        ['href' => $rfRoute('taladsod.home', [], url('/taladsod')), 'label' => 'ตลาดสด ใกล้บ้าน'],
        ['href' => $rfRoute('official-shop.index', [], url('/official-shop')), 'label' => 'ร้านค้าทางการ'],
        ['href' => $rfRoute('tarot.index', [], url('/tarot')), 'label' => 'ดูดวงไพ่ทาโรต์'],
    ];
    $rfJoin = [
        ['href' => $rfUser ? $rfRoute('user.rider.dashboard') : $rfRoute('taladsod.landing.rider'), 'label' => 'สมัครเป็นไรเดอร์'],
        ['href' => $rfRoute('user.seller-apply.index', [], $rfLoginUrl), 'label' => 'เปิดร้านค้าออนไลน์'],
        ['href' => $rfRoute('taladsod.landing.seller'), 'label' => 'เปิดร้านตลาดสด / รถเข็น'],
    ];
@endphp

<footer class="nv-foot">
    <img class="nv-foot__arch" src="{{ asset('images/nova/brand/tabbar-kanok-arch.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
    <div class="nv-wrap">
        <div class="nv-foot__grid">
            <div>
                <x-theme-v4.brand-logo :height="48" variant="dark" />
                <p class="nv-foot__about">ซูเปอร์แอปของคนไทย ช้อปของดี ตลาดสดใกล้บ้าน ไรเดอร์ ร้านค้า และดูดวง รวมไว้ในที่เดียว</p>
            </div>
            <div>
                <h4>ร้านค้า &amp; บริการ</h4>
                <ul>@foreach($rfShop as $l)<li><a href="{{ $l['href'] }}">{{ $l['label'] }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h4>ร่วมงานกับเรา</h4>
                <ul>@foreach($rfJoin as $l)<li><a href="{{ $l['href'] }}">{{ $l['label'] }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h4>บริษัท &amp; นโยบาย</h4>
                <ul>
                    <li><a href="{{ $rfRoute('page.show', 'about-us') }}">เกี่ยวกับเรา</a></li>
                    <li><a href="{{ $rfRoute('page.show', 'contact') }}">ติดต่อเรา</a></li>
                    <li><a href="{{ $rfRoute('page.show', 'faq') }}">คำถามที่พบบ่อย</a></li>
                    <li><a href="{{ $rfRoute('wiki.index') }}">วิกิแพลตฟอร์ม</a></li>
                    <li><a href="{{ $rfRoute('terms-of-service.html') }}">ข้อกำหนดการใช้งาน</a></li>
                    <li><a href="{{ $rfRoute('privacy-policy.html') }}">นโยบายความเป็นส่วนตัว</a></li>
                    <li><a href="{{ $rfRoute('cookie-policy') }}">นโยบายคุกกี้</a></li>
                    <li><a href="{{ $rfRoute('software-warranty') }}">การรับประกันซอฟต์แวร์</a></li>
                </ul>
            </div>
        </div>
        <div class="nv-foot__copy">
            <span>© {{ date('Y') + 543 }} ไทยพร๊อมท์ · Thai Prompt — แพลตฟอร์มคนไทย เพื่อคนไทย · พัฒนาโดย <a href="https://xman4289.com" target="_blank" rel="noopener noreferrer">บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด</a></span>
            <span><a href="{{ $rfUser ? $rfDashUrl : $rfLoginUrl }}">{{ $rfUser ? 'แดชบอร์ด' : 'เข้าสู่ระบบ' }}</a> · THAIPROMPT.ONLINE</span>
        </div>
    </div>
</footer>
