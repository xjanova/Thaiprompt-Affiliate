{{--
 | แถบแอป Thai Prompt APP + คลิปแนะนำ (น้องพร้อม 2 นาที) — include จาก storefront/nova/top.blade.php
 |   วางเป็นส่วนแรกของแผ่นงาช้าง ต่อจากฮีโร่ทันที (เจ้าของสั่ง: "สลับส่วนโหลดแอปขึ้นมาด้านบน ให้คนเห็นง่ายในมือถือ")
 | ปุ่มโหลดแอป: APK บนเซิร์ฟเวอร์เรา (AppDownloadService → /app/download) · Play Store จาก site_settings.app_playstore_url
 |   หลังบ้านปิด "ส่วนดาวน์โหลดแอป" = ซ่อนปุ่ม/QR แต่ยังโชว์คลิปแนะนำ
 | คลิป: settings.intro_video_url / intro_video_poster (ว่าง = ไฟล์ใน storage/app/public/videos/intro/ ที่ deploy ไม่ลบ)
 --}}
@php
    $abR = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $abImg = fn (string $p) => asset('images/nova/'.$p);

    $abDownloads = app(\App\Services\AppDownloadService::class);
    $abSection = $abDownloads->sectionOn();
    $abPlay = $abSection ? $abDownloads->playStoreUrl() : null;
    // APK บนเซิร์ฟเวอร์เรา (หรือลิงก์ APK สำรองที่หลังบ้านตั้งไว้) → ปุ่มชี้ /app/download เสมอ
    $abApk = $abSection ? $abDownloads->available() : null;
    $abApkHref = $abSection && ($abApk || $abDownloads->externalUrl()) && \Illuminate\Support\Facades\Route::has('app.download') ? route('app.download') : null;
    $abQr = $abApkHref ? $abDownloads->qrSvg($abDownloads->downloadPageUrl()) : null;

    // คลิปแนะนำ — ตั้งใหม่ได้จากตาราง settings โดยไม่ต้อง deploy
    $abVideo = (string) (\App\Models\Setting::get('intro_video_url') ?: asset('storage/videos/intro/thaiprompt-intro-v1.mp4'));
    $abPoster = (string) (\App\Models\Setting::get('intro_video_poster') ?: asset('storage/videos/intro/intro_poster.jpg').'?v=2');
@endphp

<div class="nv-appwrap nv-appwrap--top" id="nv-app">
    <img class="nv-deco nv-deco--front nv-hide-m" src="{{ $abImg('deco/garland.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" style="--x:-18px;--y:-34px;--w:96px;--z:8;--t:6s;--r0:-8deg;--r1:-2deg">
    <section class="nv-appband nv-rv" aria-labelledby="nv-app-title">
        <img class="nv-appband__kanok" src="{{ $abImg('brand/kanok-gold.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
        <div>
            <p class="nv-kicker" style="color:var(--nv-gold-300);">THAI PROMPT APP</p>
            <h2 id="nv-app-title">ทุกบริการ <span class="nv-foil">ในแอปเดียว</span></h2>
            <p>ช้อปของ ตลาดสด เรียกไรเดอร์ จัดการร้าน และกระเป๋าเงิน รวมไว้ในแอปที่ออกแบบชุดเดียวกับเว็บ · กดดูคลิปแนะนำจากน้องพร้อมได้เลย</p>
            <ul>
                <li><i class="fas fa-circle-check" aria-hidden="true"></i>แจ้งเตือนออเดอร์และข้อความทันที</li>
                <li><i class="fas fa-circle-check" aria-hidden="true"></i>กระเป๋าเงินในตัว เติม โอน ถอน ได้ในแอป</li>
                <li><i class="fas fa-circle-check" aria-hidden="true"></i>ผู้ขาย ไรเดอร์ และร้านตลาดสด จัดการงานได้จากมือถือ</li>
            </ul>
            @if($abSection)
            <div class="nv-appband__get">
                <div>
                    <div class="nv-appband__cta">
                        @if($abApkHref)
                            <a href="{{ $abApkHref }}" class="nv-btn nv-btn--gold nv-btn--lg" rel="nofollow"><i class="fab fa-android" aria-hidden="true"></i> ดาวน์โหลดแอป Android</a>
                        @endif
                        @if($abPlay)
                            <a href="{{ $abPlay }}" class="nv-btn {{ $abApkHref ? 'nv-btn--ghost' : 'nv-btn--gold' }} nv-btn--lg" target="_blank" rel="noopener"><i class="fab fa-google-play" aria-hidden="true"></i> Google Play</a>
                        @elseif(! $abApkHref)
                            <span class="nv-btn nv-btn--gold nv-btn--lg" aria-disabled="true"><i class="fab fa-google-play" aria-hidden="true"></i> เร็วๆ นี้บน Google Play</span>
                        @endif
                        @guest
                            @if(! $abApkHref && ! $abPlay)
                                <a href="{{ $abR('register', [], url('/register')) }}" class="nv-btn nv-btn--ghost nv-btn--lg">สมัครสมาชิกไว้ก่อน</a>
                            @endif
                        @endguest
                    </div>
                    @if($abApk)
                        <p class="nv-appband__meta">Android 7 ขึ้นไป · เวอร์ชัน {{ $abApk['version'] }} · {{ number_format($abApk['size'] / 1048576) }} MB · โหลดตรงจากเซิร์ฟเวอร์ Thai Prompt</p>
                        <p class="nv-appband__hint"><i class="fas fa-circle-info" aria-hidden="true"></i> มือถือถามตอนติดตั้ง ให้กด “อนุญาตจากแหล่งที่มานี้” · iPhone เร็วๆ นี้</p>
                    @endif
                </div>
                @if($abQr)
                    <div class="nv-qr" aria-hidden="true">
                        <div class="nv-qr__code">{!! $abQr !!}</div>
                        <span>สแกนโหลดบนมือถือ</span>
                    </div>
                @endif
            </div>
            @endif
        </div>

        {{-- มือถือกรอบทอง เล่นคลิปแนะนำจริง (โหลดไฟล์เมื่อกดเล่นเท่านั้น — preload=none ไม่กินเน็ตคนที่ไม่ดู) --}}
        <div class="nv-phone nv-phone--video" id="nv-intro">
            <div class="nv-phone__scr">
                <video class="nv-phone__vid" id="nv-intro-video" src="{{ $abVideo }}" poster="{{ $abPoster }}" preload="none" playsinline controlslist="nodownload noplaybackrate" disablepictureinpicture
                       aria-label="คลิปแนะนำแอป Thai Prompt จากน้องพร้อม ความยาว 2 นาที 45 วินาที"></video>
                <button type="button" class="nv-phone__play" id="nv-intro-play" aria-controls="nv-intro-video">
                    <span class="nv-phone__play-ic" aria-hidden="true"><i class="fas fa-play"></i></span>
                    <b>ดูคลิปแนะนำ</b>
                    <small>น้องพร้อมเล่าให้ฟังใน 2 นาที · เปิดเสียงได้</small>
                </button>
            </div>
        </div>
    </section>
</div>

<script>
    // คลิปแนะนำ: กดปุ่มบนมือถือจำลอง หรือปุ่ม "ดูคลิป" บนฮีโร่ (data-nv-intro) → เลื่อนมาที่แถบแอปแล้วเล่นพร้อมเสียง
    (function () {
        var box = document.getElementById('nv-intro');
        var vid = document.getElementById('nv-intro-video');
        var btn = document.getElementById('nv-intro-play');
        if (!box || !vid || !btn) return;

        function play() {
            box.classList.add('is-playing');
            vid.setAttribute('controls', '');
            var p = vid.play();
            if (p && p.catch) p.catch(function () { /* เบราว์เซอร์บล็อกเสียง → ให้ผู้ใช้กดปุ่มเล่นของวิดีโอเอง */ });
        }

        btn.addEventListener('click', play);
        vid.addEventListener('ended', function () {
            box.classList.remove('is-playing');
            vid.removeAttribute('controls');
            try { vid.currentTime = 0; } catch (e) {}
        });
        document.querySelectorAll('[data-nv-intro]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                play();
            });
        });
    })();
</script>
