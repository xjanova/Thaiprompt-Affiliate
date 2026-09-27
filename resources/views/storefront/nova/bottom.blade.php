{{--
 | หน้าแรกธีม "โนวา" ส่วนล่าง — include จาก storefront/index.blade.php ภายในแผ่นงาช้างแผ่นที่ 2
 |   สร้างรายได้ (ไรเดอร์ / เปิดร้าน / ชวนเพื่อน)
 | แถบแอป + คลิปแนะนำ ย้ายขึ้นไปต่อจากฮีโร่แล้ว → storefront/nova/app-band.blade.php
 --}}
@php
    $rbR = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $rbUser = auth()->user();
    $rbImg = fn (string $p) => asset('images/nova/'.$p);

    $rbEarn = [
        ['img' => 'earn/rider.webp', 'kicker' => 'RIDER', 'title' => 'เป็นไรเดอร์', 'text' => 'รับงานส่งใกล้บ้าน เลือกเวลาทำงานเอง รายได้เข้ากระเป๋าเงินทุกวัน', 'cta' => 'สมัครไรเดอร์',
            'href' => $rbUser ? $rbR('user.rider.dashboard') : $rbR('taladsod.landing.rider')],
        ['img' => 'earn/store.webp', 'kicker' => 'SELLER', 'title' => 'เปิดร้านกับเรา', 'text' => 'ลงสินค้า รับออเดอร์ แชทกับลูกค้า ครบในแอปเดียว ทั้งร้านออนไลน์และตลาดสด', 'cta' => 'เปิดร้านเลย',
            'href' => $rbR('taladsod.landing.seller')],
        ['img' => 'earn/earn.webp', 'kicker' => 'REFERRAL', 'title' => 'ชวนเพื่อน รับรายได้', 'text' => 'แชร์ลิงก์ของคุณ รับส่วนแบ่งเมื่อเพื่อนสั่งซื้อ ดูยอดได้แบบเรียลไทม์', 'cta' => 'เริ่มชวนเพื่อน',
            'href' => $rbUser ? $rbR('user.mlm.referral', [], $rbR('user.dashboard')) : $rbR('register', [], url('/register'))],
    ];
@endphp

<div class="nv-wrap">
    <section class="nv-sec nv-sec--decor" aria-labelledby="nv-earn-title">
        <img class="nv-deco nv-hide-m" src="{{ $rbImg('deco/scooter.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:calc(100% - 178px);--y:18px;--w:168px;--z:10;--t:8s;--r0:-2deg;--r1:2deg">
        <div class="nv-sec__head nv-rv">
            <div><p class="nv-kicker">GROW WITH US</p><h2 class="nv-sec__title" id="nv-earn-title">สร้างรายได้ไปกับ <em>Thai Prompt</em></h2></div>
        </div>
        <div class="nv-earn">
            @foreach($rbEarn as $i => $e)
                <a href="{{ $e['href'] }}" class="nv-ecard nv-rv" style="--d:{{ $i }}">
                    <img src="{{ $rbImg($e['img']) }}" alt="" width="640" height="960" loading="lazy" decoding="async">
                    <p class="nv-kicker">{{ $e['kicker'] }}</p>
                    <h3>{{ $e['title'] }}</h3>
                    <p>{{ $e['text'] }}</p>
                    <span class="nv-btn nv-btn--gold">{{ $e['cta'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

</div>
