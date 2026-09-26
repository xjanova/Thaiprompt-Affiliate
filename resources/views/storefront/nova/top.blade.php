{{--
 | หน้าแรกธีม "โนวา" ส่วนบน — include จาก storefront/index.blade.php (โหมดหน้าแรกเท่านั้น)
 |   1) ฮีโร่ฉากกลางคืน + การ์ดบริการ 8 ใบ (การ์ดกริด 3D แบบ "โซลูชั่นที่ตอบโจทย์")
 |   2) แผ่นงาช้าง: สถิติ + ดีลเด็ด (ใช้ x-theme-v4.product-card ตัวเดิม ปุ่มตะกร้า/รายการโปรดทำงานเหมือนเดิม)
 |   3) แถบกลางคืน: หมวดหมู่สินค้า (ภาพชุดใหม่ public/images/nova/cat/*.webp จับคู่ด้วย slug/ชื่อหมวด)
 | ใช้ตัวแปรจากหน้าแม่: $stats $flashDeals $flashDealEndTime $flashDealCheckedAt $categories $sfFavIds $sfCoverService
 --}}
@php
    $nvR = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $nvUser = auth()->user();
    $nvImg = fn (string $p) => asset('images/nova/'.$p);
    $nvAll = (int) ($stats['all'] ?? 0);
    // มีไฟล์แอปบนเซิร์ฟเวอร์ให้โหลดแล้วหรือยัง (การ์ดแอปเปลี่ยนป้าย/ปุ่มเป็น "โหลดแอป")
    // หลังบ้านปิด "ส่วนดาวน์โหลดแอป" = ไม่มีแถบแอปท้ายหน้า → ตัดการ์ดแอปออกด้วย (ไม่งั้นกดแล้วไม่ไปไหน)
    $nvDownloads = app(\App\Services\AppDownloadService::class);
    $nvAppSection = $nvDownloads->sectionOn();
    $nvApkReady = $nvDownloads->available() !== null;

    // การ์ดบริการ 8 ใบบนฮีโร่ — say = คำพูดของไกด์ตอนชี้การ์ด
    $nvServices = [
        ['th' => 'ช้อปปิ้ง', 'en' => 'SHOPPING · DEALS · POINTS', 'desc' => ($nvAll > 0 ? 'สินค้าพร้อมขาย '.number_format($nvAll).' รายการ' : 'สินค้าคัดสรร').' จากร้านทางการและพาร์ตเนอร์ สะสมคะแนนได้ทุกคำสั่งซื้อ',
            'img' => 'svc/shop.webp', 'icon' => 'fa-bag-shopping', 'accent' => '#f0c96a', 'badge' => 'ยอดนิยม', 'go' => 'เริ่มช้อป', 'href' => '#products', 'say' => 'ช้อปปิ้ง — ของดี ราคาคุ้ม ส่งทั่วไทยค่ะ'],
        ['th' => 'ตลาดสด', 'en' => 'FRESH · LOCAL · DELIVERY', 'desc' => 'ผัก ผลไม้ และของสดจากแม่ค้าในชุมชน สั่งง่าย ไรเดอร์ในพื้นที่ส่งถึงหน้าบ้าน',
            'img' => 'svc/market.webp', 'icon' => 'fa-basket-shopping', 'accent' => '#8fdc9a', 'go' => 'เข้าตลาดสด', 'href' => $nvR('taladsod.home', [], url('/taladsod')), 'say' => 'ตลาดสด — ของสดจากตลาดใกล้บ้านค่ะ'],
        ['th' => 'รถเข็นใกล้ฉัน', 'en' => 'STREET FOOD · NEARBY', 'desc' => 'ดูรถเข็นและร้านริมทางที่เปิดอยู่รอบตัวคุณ กดติดตามร้านโปรดแล้วรับแจ้งเตือนเมื่อร้านเปิด',
            'img' => 'svc/cart.webp', 'icon' => 'fa-bowl-food', 'accent' => '#ffb36b', 'badge' => 'ใหม่', 'go' => 'ดูร้านใกล้ฉัน', 'href' => $nvR('taladsod.home', [], url('/taladsod')).'#near-me', 'say' => 'รถเข็นใกล้ฉัน — ร้านอร่อยที่เปิดอยู่ตอนนี้ค่ะ'],
        ['th' => 'ไรเดอร์', 'en' => 'RIDER · FLEXIBLE · DAILY', 'desc' => 'สมัครเป็นไรเดอร์ รับงานส่งใกล้บ้าน เลือกเวลาทำงานเอง รายได้เข้ากระเป๋าเงิน',
            'img' => 'svc/rider.webp', 'icon' => 'fa-motorcycle', 'accent' => '#7cc4ff', 'go' => 'สมัครไรเดอร์', 'href' => $nvUser ? $nvR('user.rider.dashboard') : $nvR('taladsod.landing.rider'), 'say' => 'ไรเดอร์ — ขับส่งของ มีรายได้ทุกวันค่ะ'],
        ['th' => 'เปิดร้าน', 'en' => 'SELLER · ONLINE · MARKET', 'desc' => 'ลงสินค้า รับออเดอร์ แชทกับลูกค้า และดูรายได้ ครบในแอปเดียว ทั้งร้านออนไลน์และร้านตลาดสด',
            'img' => 'svc/store.webp', 'icon' => 'fa-store', 'accent' => '#f2b8a0', 'go' => 'เปิดร้านกับเรา', 'href' => $nvR('taladsod.landing.seller'), 'say' => 'เปิดร้าน — เปิดร้านออนไลน์ได้ในไม่กี่นาทีค่ะ'],
        ['th' => 'ดูดวง', 'en' => 'TAROT · DAILY · LOVE', 'desc' => 'ดูดวงไพ่ทาโรต์และดวงรายวัน ถามเรื่องงาน การเงิน ความรัก',
            'img' => 'svc/fortune.webp', 'icon' => 'fa-moon', 'accent' => '#b9a2ff', 'go' => 'ดูดวงเลย', 'href' => $nvR('tarot.index', [], url('/tarot')), 'say' => 'ดูดวง — ไพ่ทาโรต์และดวงรายวันค่ะ'],
        ['th' => 'ชวนเพื่อน', 'en' => 'REFERRAL · EARN', 'desc' => 'ชวนเพื่อนมาใช้ Thai Prompt ด้วยลิงก์ของคุณ รับส่วนแบ่งเมื่อเพื่อนสั่งซื้อ ดูยอดได้แบบเรียลไทม์',
            'img' => 'svc/earn.webp', 'icon' => 'fa-gift', 'accent' => '#ffd36e', 'go' => 'เริ่มชวนเพื่อน', 'href' => $nvUser ? $nvR('user.mlm.referral', [], $nvR('user.dashboard')) : $nvR('register', [], url('/register')), 'say' => 'ชวนเพื่อน — แชร์ลิงก์ รับรายได้ร่วมค่ะ'],
        ['th' => 'แอป Thai Prompt', 'en' => 'THE APP · ANDROID', 'desc' => 'ช้อป ตลาดสด ไรเดอร์ ร้านค้า และกระเป๋าเงิน รวมไว้ในแอปเดียว แจ้งเตือนออเดอร์ทันที',
            'apps' => ['basket', 'bag', 'scooter', 'wallet'], 'icon' => 'fa-mobile-screen', 'accent' => '#9cc3ff', 'badge' => $nvApkReady ? 'โหลดฟรี' : 'แอป', 'go' => $nvApkReady ? 'โหลดแอป' : 'ดูแอป', 'href' => '#nv-app', 'say' => $nvApkReady ? 'แอป Thai Prompt — กดโหลดจากเว็บเราได้เลยค่ะ' : 'แอป Thai Prompt — ทุกบริการในแอปเดียวค่ะ'],
    ];
    if (! $nvAppSection) {
        $nvServices = array_values(array_filter($nvServices, fn ($s) => ($s['href'] ?? '') !== '#nv-app'));
    }

    // หมวดหมู่: จับคู่ภาพ/สี/ไอคอนด้วย slug หรือชื่อหมวด (ลำดับสำคัญ: สุขภาพก่อนอาหาร เพราะ "อาหารเสริม")
    $nvCatKeys = [
        'wallet' => ['wallet', 'topup', 'top-up', 'เติมเงิน'],
        'electronics' => ['electronic', 'gadget', 'อิเล็กทรอ'],
        'fashion' => ['fashion', 'apparel', 'แฟชั่น', 'เสื้อผ้า'],
        'beauty' => ['beauty', 'cosmetic', 'ความงาม'],
        'health' => ['health', 'supplement', 'vitamin', 'สุขภาพ', 'อาหารเสริม'],
        'home' => ['home', 'garden', 'บ้าน'],
        'sports' => ['sport', 'outdoor', 'fitness', 'กีฬา'],
        'books' => ['book', 'stationery', 'หนังสือ', 'เครื่องเขียน'],
        'toys' => ['toy', 'hobb', 'ของเล่น'],
        'food' => ['food', 'beverage', 'drink', 'snack', 'อาหาร', 'เครื่องดื่ม'],
        'pets' => ['pet', 'สัตว์เลี้ยง'],
        'amulet' => ['amulet', 'lucky', 'สายมู', 'เครื่องราง', 'มงคล'],
    ];
    $nvCatMeta = [
        'electronics' => ['#7cc4ff', 'MOBILE · LAPTOP · AUDIO', 'fas fa-laptop', 'มือถือ โน้ตบุ๊ก หูฟัง และแกดเจ็ตรุ่นใหม่ ราคาคุ้ม'],
        'fashion' => ['#f2a7c3', 'APPAREL · SHOES · BAGS', 'fas fa-shirt', 'เสื้อผ้า รองเท้า กระเป๋า และเครื่องประดับ'],
        'beauty' => ['#f5b3a0', 'SKINCARE · MAKEUP', 'fas fa-wand-magic-sparkles', 'สกินแคร์ เครื่องสำอาง และของใช้ส่วนตัว'],
        'home' => ['#9bdc8f', 'HOME · LIVING · GARDEN', 'fas fa-couch', 'ของแต่งบ้าน เครื่องใช้ในบ้าน และอุปกรณ์ทำสวน'],
        'sports' => ['#7fe0c8', 'FITNESS · OUTDOOR', 'fas fa-dumbbell', 'อุปกรณ์ออกกำลังกาย แคมปิ้ง และกีฬากลางแจ้ง'],
        'books' => ['#d9c08e', 'BOOKS · STATIONERY', 'fas fa-book-open', 'หนังสือ ปากกา สมุด และอุปกรณ์การเรียน'],
        'toys' => ['#ffb36b', 'TOYS · HOBBIES · DIY', 'fas fa-gamepad', 'ของเล่นเด็ก งานประดิษฐ์ และของสะสม'],
        'food' => ['#ffcf6b', 'SNACKS · DRINKS', 'fas fa-bowl-food', 'ขนม เครื่องดื่ม และของกินพร้อมส่ง'],
        'health' => ['#8fdc9a', 'VITAMINS · CARE', 'fas fa-heart-pulse', 'วิตามิน อาหารเสริม และอุปกรณ์ดูแลสุขภาพ'],
        'pets' => ['#f0c96a', 'DOGS · CATS', 'fas fa-paw', 'อาหาร ขนม และของใช้สำหรับน้องหมาน้องแมว'],
        'amulet' => ['#b9a2ff', 'AMULETS · LUCK', 'fas fa-spa', 'เครื่องราง ของมงคล และของเสริมดวง'],
        'wallet' => ['#f5d27f', 'TOP-UP · POINTS', 'fas fa-wallet', 'เติมเงินเข้ากระเป๋า จ่ายไว ได้คะแนนสะสม'],
    ];
    $nvCatKey = function ($cat) use ($nvCatKeys) {
        $hay = mb_strtolower(($cat->slug ?? '').' '.($cat->name ?? ''));
        foreach ($nvCatKeys as $key => $needles) {
            foreach ($needles as $n) {
                if (str_contains($hay, $n)) {
                    return $key;
                }
            }
        }

        return null;
    };
    $nvDeals = ($flashDeals ?? collect());
@endphp

{{-- ════════ ฮีโร่ ════════ --}}
<section id="nv-hero" class="nv-hero" aria-labelledby="nv-hero-title">
    <div class="nv-bg" aria-hidden="true">
        <div class="nv-sky"></div>
        <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
        <div class="nv-temple"></div>
        <canvas id="nv-fx"></canvas>
        <div class="nv-corner nv-corner--l"><img src="{{ $nvImg('brand/kanok-gold.webp') }}" alt="" decoding="async"><span class="nv-shine"></span></div>
        <div class="nv-corner nv-corner--r"><img src="{{ $nvImg('brand/kanok-gold.webp') }}" alt="" decoding="async"><span class="nv-shine"></span></div>
        <div class="nv-vignette"></div>
        <div class="nv-grain"></div>
        <div class="nv-flash" id="nv-flash"></div>
    </div>
    <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/lantern.webp') }}" alt="" aria-hidden="true" decoding="async" style="--x:3.2%;--y:36%;--w:66px;--z:14;--t:8s;--r0:-3deg;--r1:3deg">
    <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/crystal.webp') }}" alt="" aria-hidden="true" decoding="async" style="--x:91.5%;--y:24%;--w:84px;--z:20;--t:9s;--dl:-3s">
    <img class="nv-deco nv-hide-m nv-hide-t" src="{{ $nvImg('deco/lotus.webp') }}" alt="" aria-hidden="true" decoding="async" style="--x:91%;--y:68%;--w:104px;--z:10;--t:10s;--dl:-5s;--r0:-2deg;--r1:2deg">

    <div class="nv-head">
        <p class="nv-eyebrow"><span class="nv-eyebrow__line"></span>Thai Prompt <b>ซูเปอร์แอปของคนไทย</b><span class="nv-eyebrow__line"></span></p>
        <h1 class="nv-title" id="nv-hero-title"><span class="nv-nw">ทุกเรื่องใกล้ตัว</span> <span class="nv-foil nv-nw">จบในที่เดียว</span></h1>
        <p class="nv-sub"><span class="nv-nw">เลือกบริการที่ตอบโจทย์คุณ</span> <span class="nv-nw">— ช้อป กิน ส่ง ขาย และดูดวง</span> <span class="nv-nw">ครบในที่เดียว</span></p>
    </div>

    <div class="nv-g3d">
        <div class="nv-tgrid" id="nv-svc-grid">
            @foreach($nvServices as $i => $s)
                <a href="{{ $s['href'] }}" class="nv-tcard nv-rv" data-i="{{ $i }}" data-name="{{ $s['th'] }}" data-say="{{ $s['say'] }}" style="--accent:{{ $s['accent'] }};--d:{{ $i }};">
                    <span class="nv-tcard__media" aria-hidden="true">
                        @if(! empty($s['img']))
                            <img src="{{ $nvImg($s['img']) }}" alt="" width="720" height="360" decoding="async" @if($i >= 4) loading="lazy" @endif draggable="false">
                        @endif
                        @if(! empty($s['apps']))
                            <span class="nv-tcard__apps">@foreach($s['apps'] as $app)<img src="{{ $nvImg('icons/'.$app.'.webp') }}" alt="" width="64" height="64" loading="lazy" decoding="async">@endforeach</span>
                        @endif
                    </span>
                    @if(! empty($s['badge']))<span class="nv-tcard__badge">{{ $s['badge'] }}</span>@endif
                    <span class="nv-tcard__icon" aria-hidden="true"><i class="fas {{ $s['icon'] }}"></i></span>
                    <span class="nv-tcard__title">{{ $s['th'] }}</span>
                    <span class="nv-tcard__en" aria-hidden="true">{{ $s['en'] }}</span>
                    <span class="nv-tcard__body">{{ $s['desc'] }}</span>
                    <span class="nv-tcard__foot"><span class="nv-tcard__go">{{ $s['go'] }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></span>
                    <span class="nv-tcard__sheen" aria-hidden="true"></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════ แผ่นงาช้าง 1 — สถิติ + ดีลเด็ด ════════ --}}
<div class="nv-sheet" id="nv-deals">
    <img class="nv-sheet__crest" src="{{ $nvImg('brand/tabbar-kanok-arch.webp') }}" alt="" aria-hidden="true" decoding="async">
    <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/coins.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:91%;--y:26px;--w:118px;--z:12;--t:8s;--dl:-2s">
    <img class="nv-deco nv-hide-m nv-hide-t" src="{{ $nvImg('deco/lotus.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:.8%;--y:62%;--w:128px;--z:9;--t:11s;--r0:-2deg;--r1:2deg">
    <div class="nv-wrap">
        <div class="nv-stats">
            <div class="nv-stat nv-rv" style="--d:0"><span class="nv-stat__ic"><i class="fas fa-bag-shopping" aria-hidden="true"></i></span><div><b>{{ number_format($nvAll) }}</b><span>สินค้าพร้อมขาย</span></div></div>
            <div class="nv-stat nv-rv" style="--d:1"><span class="nv-stat__ic"><i class="fas fa-store" aria-hidden="true"></i></span><div><b>{{ number_format((int) ($stats['stores'] ?? 0)) }}</b><span>ร้านค้าในระบบ</span></div></div>
            <div class="nv-stat nv-rv" style="--d:2"><span class="nv-stat__ic"><i class="fas fa-motorcycle" aria-hidden="true"></i></span><div><b>ไรเดอร์</b><span>ส่งไวในพื้นที่</span></div></div>
            <div class="nv-stat nv-rv" style="--d:3"><span class="nv-stat__ic"><i class="fas fa-wallet" aria-hidden="true"></i></span><div><b>กระเป๋าเงิน</b><span>จ่ายไว ถอนง่าย</span></div></div>
        </div>

        @if($nvDeals->count() > 0)
            <section class="nv-sec" aria-labelledby="nv-deals-title"
                     x-data="{ end: new Date({{ \Illuminate\Support\Js::from($flashDealEndTime ?? now()->addHours(3)->toIso8601String()) }}).getTime(), h: '00', m: '00', s: '00',
                               tick() { const d = Math.max(0, this.end - Date.now()); this.h = String(Math.floor(d / 3600000)).padStart(2, '0'); this.m = String(Math.floor(d % 3600000 / 60000)).padStart(2, '0'); this.s = String(Math.floor(d % 60000 / 1000)).padStart(2, '0'); } }"
                     x-init="tick(); setInterval(() => tick(), 1000)">
                <div class="nv-sec__head nv-rv">
                    <div>
                        <p class="nv-kicker">FLASH DEALS</p>
                        <h2 class="nv-sec__title" id="nv-deals-title">ดีลเด็ด <em>ราคายืนยันแล้ว</em></h2>
                    </div>
                    <span class="nv-timer"><span class="nv-nw">ตรวจราคารอบถัดไป</span> <b x-text="h">00</b><b x-text="m">00</b><b x-text="s">00</b>
                        @if($flashDealCheckedAt)<span class="nv-timer__last">· ล่าสุด {{ $flashDealCheckedAt->timezone('Asia/Bangkok')->format('H:i') }} น.</span>@endif
                    </span>
                    <a href="{{ route('storefront.index', ['deals' => 1, 'sort_by' => 'discount']) }}" class="nv-more">ดูดีลทั้งหมด <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="nv-rail">
                    @foreach($nvDeals as $deal)
                        <div><x-theme-v4.product-card :product="$deal" :favorited="in_array((int) $deal->id, $sfFavIds, true)" /></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

{{-- ════════ แถบกลางคืน — หมวดหมู่สินค้า ════════ --}}
@if($categories && $categories->count() > 0)
    <section id="nv-cats" class="nv-night" aria-labelledby="nv-cats-title">
        <div class="nv-bg" aria-hidden="true">
            <div class="nv-sky"></div>
            <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
            <div class="nv-core"></div>
            <canvas id="nv-fx2"></canvas>
            <div class="nv-vignette"></div>
            <div class="nv-flash" id="nv-flash2"></div>
        </div>
        <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/crystal.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:3%;--y:14%;--w:112px;--z:22;--t:9s;--r0:-8deg;--r1:6deg">
        <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/crystal.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:90.5%;--y:56%;--w:86px;--z:28;--t:7.5s;--dl:-4s;--r0:10deg;--r1:18deg">
        <img class="nv-deco nv-hide-m" src="{{ $nvImg('deco/lantern.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:89%;--y:9%;--w:62px;--z:12;--t:8.5s;--dl:-2s">
        <img class="nv-deco nv-hide-m nv-hide-t" src="{{ $nvImg('deco/lotus.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:3.5%;--y:70%;--w:100px;--z:16;--t:10s;--dl:-6s;--r0:-3deg;--r1:3deg">
        <div class="nv-wrap nv-night__in">
            <header class="nv-night__head nv-rv">
                <p class="nv-pill"><i></i>หมวดหมู่สินค้า <b>/ SHOP BY CATEGORY</b></p>
                <h2 class="nv-night__title" id="nv-cats-title"><span class="nv-nw">ช้อปตามหมวดหมู่</span><br><span class="nv-foil nv-nw">ที่ตอบโจทย์ทุกความต้องการ</span></h2>
                <p class="nv-night__sub">สินค้าคัดสรรจากร้านทางการและพาร์ตเนอร์ ส่งทั่วไทย สะสมคะแนนได้ทุกคำสั่งซื้อ</p>
            </header>
        </div>
        <div class="nv-g3d">
            <div class="nv-tgrid" id="nv-cat-grid">
                @foreach($categories->take(12) as $ci => $cat)
                    @php
                        $nvKey = $nvCatKey($cat);
                        $nvMeta = $nvKey ? $nvCatMeta[$nvKey] : null;
                        $nvCatCover = $nvKey ? null : $sfCoverService->cover($cat);
                        $nvCatImg = $nvKey ? $nvImg('cat/'.$nvKey.'.webp') : ($nvCatCover['urls'][0] ?? null);
                        $nvCatCount = (int) ($cat->total_products_count ?? $cat->products_count ?? 0);
                        $nvCatDesc = trim(strip_tags((string) ($cat->description ?? '')));
                        $nvCatDesc = $nvCatDesc !== '' ? \Illuminate\Support\Str::limit($nvCatDesc, 80) : ($nvMeta[3] ?? 'สินค้าคัดสรรในหมวด'.$cat->name);
                        $nvCatEn = $nvMeta[1] ?? mb_strtoupper(str_replace('-', ' · ', \Illuminate\Support\Str::limit((string) $cat->slug, 28, '')));
                    @endphp
                    <a href="{{ route('storefront.index', ['category' => $cat->slug]) }}" class="nv-tcard nv-rv" data-i="{{ $ci }}" data-name="หมวด{{ $cat->name }}"
                       data-say="หมวด{{ $cat->name }}{{ $nvCatCount > 0 ? ' มี '.number_format($nvCatCount).' รายการ' : '' }} — {{ $nvCatDesc }}ค่ะ"
                       data-go-say="ไปดูหมวด{{ $cat->name }}กันเลยค่ะ" style="--accent:{{ $nvMeta[0] ?? '#f0c96a' }};--d:{{ $ci % 4 }};">
                        <span class="nv-tcard__media" aria-hidden="true">
                            @if($nvCatImg)<img src="{{ $nvCatImg }}" alt="" width="720" height="360" loading="lazy" decoding="async" draggable="false">@endif
                        </span>
                        <span class="nv-tcard__icon" aria-hidden="true"><i class="{{ $nvMeta[2] ?? ($nvCatCover['icon'] ?? 'fas fa-tags') }}"></i></span>
                        <span class="nv-tcard__title">{{ $cat->name }}</span>
                        <span class="nv-tcard__en" aria-hidden="true">{{ $nvCatEn }}</span>
                        <span class="nv-tcard__body">{{ $nvCatDesc }}</span>
                        <span class="nv-tcard__foot">
                            <span class="nv-tcard__go">ดูสินค้า <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                            @if($nvCatCount > 0)<span class="nv-tcard__meta">{{ number_format($nvCatCount) }} รายการ</span>@endif
                        </span>
                        <span class="nv-tcard__sheen" aria-hidden="true"></span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="nv-night__cta nv-rv"><a href="#products" class="nv-btn nv-btn--gold nv-btn--lg">ดูสินค้าทั้งหมด <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    </section>
@endif
