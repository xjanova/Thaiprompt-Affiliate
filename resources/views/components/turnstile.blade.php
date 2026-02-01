{{--
    Turnstile Blade Component - Cloudflare Turnstile Widget

    ใช้งาน: <x-turnstile />
    หรือ: <x-turnstile point="checkout" />

    Component จะแสดง Turnstile widget เฉพาะเมื่อ:
    1. Turnstile enabled ในระบบ
    2. มี site_key ตั้งค่าแล้ว
    3. point ที่ระบุเปิดใช้งาน (ถ้าระบุ)

    @props
    - point: string|null - จุดที่ใช้ Turnstile (login, register, checkout, review, comment)
    - theme: string - ธีม (auto, light, dark)
    - size: string - ขนาด (normal, compact)
    - class: string - CSS class เพิ่มเติม
--}}

@props([
    'point' => null,
    'theme' => null,
    'size' => null,
])

@php
    $enabled = config('turnstile.enabled', false);
    $siteKey = config('turnstile.site_key', '');
    $pointEnabled = $point ? config("turnstile.points.{$point}", true) : true;
    $shouldShow = $enabled && !empty($siteKey) && $pointEnabled;
    $themeValue = $theme ?? config('turnstile.theme', 'auto');
    $sizeValue = $size ?? config('turnstile.size', 'normal');
@endphp

@if($shouldShow)
<div {{ $attributes->merge(['class' => 'turnstile-container my-3']) }}
     x-data="{ turnstileLoaded: false }"
     x-init="
        if (!document.getElementById('turnstile-script')) {
            const script = document.createElement('script');
            script.id = 'turnstile-script';
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
        // รอให้ Turnstile โหลดเสร็จ
        const checkLoaded = setInterval(() => {
            if (window.turnstile) {
                clearInterval(checkLoaded);
                turnstileLoaded = true;
                if ($refs.turnstileWidget) {
                    window.turnstile.render($refs.turnstileWidget, {
                        sitekey: '{{ $siteKey }}',
                        theme: '{{ $themeValue }}',
                        size: '{{ $sizeValue }}',
                        callback: function(token) {
                            const input = $refs.turnstileWidget.querySelector('input[name=cf-turnstile-response]');
                            if (!input) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'cf-turnstile-response';
                                hiddenInput.value = token;
                                $refs.turnstileWidget.appendChild(hiddenInput);
                            }
                        }
                    });
                }
            }
        }, 100);
        // หยุดเช็คหลัง 10 วินาที
        setTimeout(() => clearInterval(checkLoaded), 10000);
     ">
    <div x-ref="turnstileWidget"
         class="cf-turnstile"
         data-sitekey="{{ $siteKey }}"
         data-theme="{{ $themeValue }}"
         data-size="{{ $sizeValue }}">
    </div>

    {{-- แสดงข้อความกำลังโหลดขณะรอ Turnstile --}}
    <div x-show="!turnstileLoaded"
         class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-2">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>กำลังโหลดระบบยืนยันตัวตน...</span>
    </div>

    @error('turnstile')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>
@endif
