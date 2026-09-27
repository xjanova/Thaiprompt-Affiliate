{{--
 | หน้าข้อผิดพลาดธีมโนวา (หน้าเต็ม ไม่มี layout) — ใช้ร่วมกัน 404 / 419 / 500
 | ⚠️ ห้ามแตะฐานข้อมูล/เซสชันในไฟล์นี้ (หน้า 500 อาจแสดงตอนฐานข้อมูลล่ม) — ใช้แค่ config() กับ asset()
 |
 | ตัวแปร: $nvCode (เลขข้อผิดพลาด) · $nvTitle · $nvMessage · $nvDeco (ชื่อไฟล์ใน images/nova/deco)
 |         $nvActions = [['href' => ..., 'label' => ..., 'icon' => 'fa-house', 'primary' => true], ...]
 |         $nvDetails (ไม่บังคับ) = [['label' => 'IP ของคุณ', 'value' => '1.2.3.4', 'mono' => true], ...]
 --}}
@php
    $nvBrand = config('app.brand_name', 'Thai Prompt');
    $nvCssVer = is_file(public_path('theme-nova/nova.css')) ? filemtime(public_path('theme-nova/nova.css')) : 1;
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $nvCode }} - {{ $nvTitle }} | {{ $nvBrand }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ $nvCssVer }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body { font-family: var(--nv-font-ui); color: var(--nv-on-night); background: #060b1c; }
        .nv-err { position: relative; isolation: isolate; overflow: hidden; min-height: 100svh; display: grid; grid-template-columns: minmax(0, 1fr); place-items: center; padding: 96px 20px 60px; }
        .nv-err__logo { position: absolute; top: 22px; left: 50%; transform: translateX(-50%); z-index: 2; }
        .nv-err__logo img { height: 42px; width: auto; display: block; }
        .nv-err__card { position: relative; z-index: 1; width: min(560px, 100%); padding: 38px 32px 32px; border-radius: 30px; text-align: center; background: linear-gradient(160deg, rgba(20, 36, 84, .78), rgba(7, 13, 32, .88)); border: 1px solid rgba(245, 210, 127, .26); box-shadow: 0 40px 90px -36px rgba(0, 0, 0, .9), inset 0 1px 0 rgba(255, 255, 255, .06); }
        .nv-err__deco { width: 150px; height: auto; margin: -110px auto 6px; display: block; filter: drop-shadow(0 22px 26px rgba(0, 0, 0, .55)); animation: nv-dfloat 7s ease-in-out infinite; --r0: -4deg; --r1: 3deg; }
        .nv-err__code { margin: 0; font-family: var(--nv-font-latin); font-weight: 700; font-size: clamp(64px, 14vw, 104px); line-height: 1; letter-spacing: .06em; }
        .nv-err__title { margin: 8px 0 10px; font-family: var(--nv-font-display); font-size: clamp(24px, 4.4vw, 32px); line-height: 1.3; color: #fbf6ea; }
        .nv-err__msg { margin: 0 auto 24px; max-width: 420px; color: var(--nv-on-night-2); line-height: 1.7; }
        .nv-err__actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
        .nv-err__details { margin: -6px auto 24px; max-width: 420px; display: grid; gap: 8px; text-align: left; }
        .nv-err__row { padding: 10px 14px; border-radius: 14px; background: rgba(255, 255, 255, .04); border: 1px solid rgba(245, 210, 127, .18); }
        .nv-err__row span { display: block; font-size: 12px; letter-spacing: .04em; color: #f0c96a; }
        .nv-err__row b { display: block; margin-top: 2px; font-weight: 600; color: #fbf6ea; word-break: break-word; }
        .nv-err__row b.is-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .02em; }
        .nv-err__kanok { position: absolute; width: clamp(160px, 24vw, 320px); opacity: .22; pointer-events: none; }
        .nv-err__kanok--l { left: -8px; top: 70px; transform: scaleX(-1); }
        .nv-err__kanok--r { right: -8px; top: 70px; }
        @media (max-width: 520px) { .nv-err__card { padding: 30px 20px 24px; } .nv-err__deco { width: 116px; margin-top: -86px; } }
    </style>
</head>
<body>
<main class="nv-err">
    <div class="nv-bg" aria-hidden="true">
        <div class="nv-sky"></div>
        <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
        <div class="nv-temple"></div>
        <div class="nv-vignette"></div>
    </div>
    <img class="nv-err__kanok nv-err__kanok--l" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <img class="nv-err__kanok nv-err__kanok--r" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <a class="nv-err__logo" href="{{ url('/') }}" title="{{ $nvBrand }}">
        <picture>
            <source srcset="{{ asset('images/brand/thaiprompt-logo-dark.webp') }}" type="image/webp">
            <img src="{{ asset('images/brand/thaiprompt-logo-dark.png') }}" alt="{{ $nvBrand }}" width="192" height="56">
        </picture>
    </a>
    <section class="nv-err__card" aria-labelledby="nv-err-title">
        <img class="nv-err__deco" src="{{ asset('images/nova/deco/'.$nvDeco) }}" alt="" aria-hidden="true">
        <p class="nv-err__code"><span class="nv-foil">{{ $nvCode }}</span></p>
        <h1 class="nv-err__title" id="nv-err-title">{{ $nvTitle }}</h1>
        <p class="nv-err__msg">{{ $nvMessage }}</p>
        @if(! empty($nvDetails))
            <div class="nv-err__details">
                @foreach($nvDetails as $row)
                    <div class="nv-err__row"><span>{{ $row['label'] }}</span><b @class(['is-mono' => ! empty($row['mono'])])>{{ $row['value'] }}</b></div>
                @endforeach
            </div>
        @endif
        <div class="nv-err__actions">
            @foreach($nvActions as $act)
                <a href="{{ $act['href'] }}" class="nv-btn {{ ! empty($act['primary']) ? 'nv-btn--gold' : 'nv-btn--ghost' }} nv-btn--lg" @if(! empty($act['back'])) onclick="if (history.length > 1) { history.back(); return false; }" @endif>
                    @if(! empty($act['icon']))<i class="fas {{ $act['icon'] }}" aria-hidden="true"></i>@endif {{ $act['label'] }}
                </a>
            @endforeach
        </div>
    </section>
</main>
</body>
</html>
