{{--
 | โลโก้แบรนด์ Thai Prompt (ธีม V4) — เครื่องหมาย T ทอง + "Thai Prompt" + THAIPROMPT.ONLINE
 | ค่าเริ่มต้น: public/images/brand/thaiprompt-logo-{light,dark}.webp (มี .png สำรองสำหรับเบราว์เซอร์เก่า)
 |   - light = ตัวอักษรกรมท่า ใช้บนพื้นสว่าง · dark = ตัวอักษรงาช้าง ใช้บนพื้นกรมท่า/พื้นมืด
 |   - variant="auto" สลับเองตามโหมดมืด (class "dark" บน <html> ที่ theme engine V4 ใส่ให้)
 | ถ้าแอดมินอัปโหลดโลโก้เองใน ThemeSetting.logo_path จะใช้รูปนั้นรูปเดียวแทน (ไม่สลับตามโหมด)
 | props: height (px, ค่าเริ่มต้น 40) · variant (auto|light|dark, ค่าเริ่มต้น auto)
 --}}
@props(['height' => 40, 'variant' => 'auto'])
@php
    $tpLogoPath = optional(\App\Models\ThemeSetting::active())->logo_path ?? null;
    $tpLogoH = max(1, (int) $height);
    // ไฟล์โลโก้ใหม่ขนาด 600x175 → คำนวณความกว้างไว้ล่วงหน้า กันหน้ากระตุกตอนรูปโหลด (CLS)
    $tpLogoW = (int) round($tpLogoH * 600 / 175);
    $tpLogoImgStyle = "height:{$tpLogoH}px; width:auto; max-width:100%; display:block; object-fit:contain;";
    $tpLogoVariant = in_array($variant, ['light', 'dark'], true) ? $variant : 'auto';
@endphp
@if($tpLogoPath)
    <img src="{{ asset('storage/'.$tpLogoPath) }}" alt="Thai Prompt"
         {{ $attributes->merge(['style' => $tpLogoImgStyle]) }}>
@else
    <span {{ $attributes->merge(['style' => 'display:inline-flex; align-items:center; max-width:100%;']) }}>
        @foreach(['light', 'dark'] as $tpLogoTone)
            @continue($tpLogoVariant !== 'auto' && $tpLogoVariant !== $tpLogoTone)
            @php
                // โหมด auto: รูป light โชว์ตอนสว่าง / รูป dark โชว์ตอน html.dark (ใช้ utility dark: ของ Tailwind)
                $tpLogoToggle = $tpLogoVariant === 'auto'
                    ? ($tpLogoTone === 'light' ? 'block dark:hidden' : 'hidden dark:block')
                    : 'block';
            @endphp
            <picture class="{{ $tpLogoToggle }}">
                <source srcset="{{ asset("images/brand/thaiprompt-logo-{$tpLogoTone}.webp") }}" type="image/webp">
                <img src="{{ asset("images/brand/thaiprompt-logo-{$tpLogoTone}.png") }}" alt="Thai Prompt"
                     width="{{ $tpLogoW }}" height="{{ $tpLogoH }}" decoding="async"
                     style="{{ $tpLogoImgStyle }}">
            </picture>
        @endforeach
    </span>
@endif
