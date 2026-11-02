{{--
    เหรียญคริปโต: ไอคอนจาก public/icons/cryptocurrency/{code}.svg ถ้ามีไฟล์ ไม่งั้นเป็นอักษรตัวแรกบนพื้นทอง
    ใช้: @include('user.crypto-wallet.partials.coin', ['code' => 'BTC', 'class' => 'nvm-cw-coin--lg'])
--}}
@php
    $cwCode = strtoupper((string) ($code ?? ''));
    $cwIcon = 'icons/cryptocurrency/'.strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cwCode)).'.svg';
@endphp
<span class="nvm-cw-coin {{ $class ?? '' }}" aria-hidden="true">@if($cwCode !== '' && is_file(public_path($cwIcon)))<img src="{{ asset($cwIcon) }}" alt="" loading="lazy">@else{{ mb_substr($cwCode !== '' ? $cwCode : '?', 0, 1) }}@endif</span>
