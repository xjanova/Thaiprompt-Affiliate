{{--
 | โลโก้แบรนด์ (ธีม V4) — โลโก้แนวนอน "ไทยพร๊อมท์" แทนกล่อง TP + ตัวหนังสือ
 | ใช้รูปจาก ThemeSetting.logo_path (อัปโหลดในตั้งค่า) ถ้าไม่มีใช้ public/images/logo.png เป็นค่าเริ่มต้น
 | props: height (px, default 40)
 --}}
@props(['height' => 40])
@php
    $tpLogoPath = optional(\App\Models\ThemeSetting::active())->logo_path ?? null;
    $tpLogoUrl = $tpLogoPath ? asset('storage/'.$tpLogoPath) : asset('images/logo.png');
    $tpLogoH = (int) $height;
@endphp
<img src="{{ $tpLogoUrl }}" alt="ไทยพร๊อมท์ · ThaiPrompt"
     {{ $attributes->merge(['style' => "height:{$tpLogoH}px; width:auto; max-width:100%; display:block; object-fit:contain;"]) }}>
