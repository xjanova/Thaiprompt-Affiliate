{{--
 | หน้าแรกธีม "โนวา" ส่วนล่าง — include จาก storefront/index.blade.php ภายในแผ่นงาช้างแผ่นที่ 2
 |   สร้างรายได้ (ไรเดอร์ / เปิดร้าน / ชวนเพื่อน) + แถบแอป Thai Prompt APP
 | ปุ่มโหลดแอป: APK บนเซิร์ฟเวอร์เรา (AppDownloadService → /app/download) · Play Store จาก site_settings.app_playstore_url
 |   ไม่มีทั้งคู่ = แสดง "เร็วๆ นี้"
 --}}
@php
    $rbR = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $rbUser = auth()->user();
    $rbImg = fn (string $p) => asset('images/nova/'.$p);

    // แถบแอป: เคารพสวิตช์หลังบ้าน (ตั้งค่าเว็บไซต์ → ดาวน์โหลดแอป / APK / Play Store)
    $rbDownloads = app(\App\Services\AppDownloadService::class);
    $rbSection = $rbDownloads->sectionOn();
    $rbPlay = $rbDownloads->playStoreUrl();
    // APK บนเซิร์ฟเวอร์เรา (หรือลิงก์ APK สำรองที่หลังบ้านตั้งไว้) → ปุ่มชี้ /app/download เสมอ
    $rbApk = $rbDownloads->available();
    $rbApkHref = ($rbApk || $rbDownloads->externalUrl()) && \Illuminate\Support\Facades\Route::has('app.download') ? route('app.download') : null;
    $rbQr = $rbApkHref ? $rbDownloads->qrSvg($rbDownloads->downloadPageUrl()) : null;

    $rbEarn = [
        ['img' => 'earn/rider.webp', 'kicker' => 'RIDER', 'title' => 'เป็นไรเดอร์', 'text' => 'รับงานส่งใกล้บ้าน เลือกเวลาทำงานเอง รายได้เข้ากระเป๋าเงินทุกวัน', 'cta' => 'สมัครไรเดอร์',
            'href' => $rbUser ? $rbR('user.rider.dashboard') : $rbR('taladsod.landing.rider')],
        ['img' => 'earn/store.webp', 'kicker' => 'SELLER', 'title' => 'เปิดร้านกับเรา', 'text' => 'ลงสินค้า รับออเดอร์ แชทกับลูกค้า ครบในแอปเดียว ทั้งร้านออนไลน์และตลาดสด', 'cta' => 'เปิดร้านเลย',
            'href' => $rbR('taladsod.landing.seller')],
        ['img' => 'earn/earn.webp', 'kicker' => 'REFERRAL', 'title' => 'ชวนเพื่อน รับรายได้', 'text' => 'แชร์ลิงก์ของคุณ รับส่วนแบ่งเมื่อเพื่อนสั่งซื้อ ดูยอดได้แบบเรียลไทม์', 'cta' => 'เริ่มชวนเพื่อน',
            'href' => $rbUser ? $rbR('user.mlm.referral', [], $rbR('user.dashboard')) : $rbR('register', [], url('/register'))],
    ];
    $rbTiles = [['basket', 'ตลาดสด'], ['bag', 'ช้อปปิ้ง'], ['scooter', 'ไรเดอร์'], ['store', 'ร้านค้า'], ['cart', 'รถเข็น'], ['wallet', 'กระเป๋า'], ['gift', 'ชวนเพื่อน'], ['tarot', 'ดูดวง']];
@endphp

<div class="nv-wrap">
    <section class="nv-sec" aria-labelledby="nv-earn-title">
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

    @if($rbSection)
    <div class="nv-appwrap" id="nv-app">
        <img class="nv-deco nv-deco--front nv-hide-m" src="{{ $rbImg('deco/garland.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:-18px;--y:-34px;--w:96px;--z:8;--t:6s;--r0:-8deg;--r1:-2deg">
        <img class="nv-deco nv-deco--front nv-hide-m" src="{{ $rbImg('deco/lotus.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:calc(100% - 118px);--y:calc(100% - 70px);--w:136px;--z:10;--t:9s;--dl:-2s;--r0:-2deg;--r1:2deg">
        <section class="nv-appband nv-rv" aria-labelledby="nv-app-title">
            <img class="nv-appband__kanok" src="{{ $rbImg('brand/kanok-gold.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
            <div>
                <p class="nv-kicker" style="color:var(--nv-gold-300);">THAI PROMPT APP</p>
                <h2 id="nv-app-title">ทุกบริการ <span class="nv-foil">ในแอปเดียว</span></h2>
                <p>ช้อปของ ตลาดสด เรียกไรเดอร์ จัดการร้าน และกระเป๋าเงิน รวมไว้ในแอปที่ออกแบบชุดเดียวกับเว็บ</p>
                <ul>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i>แจ้งเตือนออเดอร์และข้อความทันที</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i>กระเป๋าเงินในตัว เติม โอน ถอน ได้ในแอป</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i>ผู้ขาย ไรเดอร์ และร้านตลาดสด จัดการงานได้จากมือถือ</li>
                </ul>
                <div class="nv-appband__get">
                    <div>
                        <div class="nv-appband__cta">
                            @if($rbApkHref)
                                <a href="{{ $rbApkHref }}" class="nv-btn nv-btn--gold nv-btn--lg" rel="nofollow"><i class="fab fa-android" aria-hidden="true"></i> ดาวน์โหลดแอป Android</a>
                            @endif
                            @if($rbPlay)
                                <a href="{{ $rbPlay }}" class="nv-btn {{ $rbApkHref ? 'nv-btn--ghost' : 'nv-btn--gold' }} nv-btn--lg" target="_blank" rel="noopener"><i class="fab fa-google-play" aria-hidden="true"></i> Google Play</a>
                            @elseif(! $rbApkHref)
                                <span class="nv-btn nv-btn--gold nv-btn--lg" aria-disabled="true"><i class="fab fa-google-play" aria-hidden="true"></i> เร็วๆ นี้บน Google Play</span>
                            @endif
                            @guest
                                @if(! $rbApkHref && ! $rbPlay)
                                    <a href="{{ $rbR('register', [], url('/register')) }}" class="nv-btn nv-btn--ghost nv-btn--lg">สมัครสมาชิกไว้ก่อน</a>
                                @endif
                            @endguest
                        </div>
                        @if($rbApk)
                            <p class="nv-appband__meta">Android 7 ขึ้นไป · เวอร์ชัน {{ $rbApk['version'] }} · {{ number_format($rbApk['size'] / 1048576) }} MB · โหลดตรงจากเซิร์ฟเวอร์ Thai Prompt</p>
                            <p class="nv-appband__hint"><i class="fas fa-circle-info" aria-hidden="true"></i> มือถือถามตอนติดตั้ง ให้กด “อนุญาตจากแหล่งที่มานี้” · iPhone เร็วๆ นี้</p>
                        @endif
                    </div>
                    @if($rbQr)
                        <div class="nv-qr" aria-hidden="true">
                            <div class="nv-qr__code">{!! $rbQr !!}</div>
                            <span>สแกนโหลดบนมือถือ</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="nv-phone" aria-hidden="true">
                <div class="nv-phone__scr">
                    <div class="nv-phone__top">
                        <img src="{{ $rbImg('brand/kanok-gold.webp') }}" alt="" loading="lazy" decoding="async">
                        <small>สวัสดีค่ะ</small><b>ยินดีต้อนรับ</b>
                        <div class="nv-phone__wallet"><span>กระเป๋าเงิน<br><b>฿0.00</b></span><span style="color:#f5d27f;">เติมเงิน ›</span></div>
                    </div>
                    <div class="nv-phone__grid">
                        @foreach($rbTiles as $t)
                            <span><img src="{{ $rbImg('icons/'.$t[0].'.webp') }}" alt="" width="42" height="42" loading="lazy" decoding="async">{{ $t[1] }}</span>
                        @endforeach
                    </div>
                    <div class="nv-phone__banner"><span>ตลาดสดใกล้บ้าน</span></div>
                    <div class="nv-phone__tab"><span>หน้าแรก</span><span>ช้อป</span><span class="nv-phone__med"><img src="{{ asset('images/brand/thaiprompt-mark.webp') }}" alt="" loading="lazy" decoding="async"></span><span>ออเดอร์</span><span>ฉัน</span></div>
                </div>
            </div>
        </section>
    </div>
    @endif
</div>
