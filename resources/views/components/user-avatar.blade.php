{{--
/**
 * User Avatar Component - ใช้แสดง avatar ของผู้ใช้ทุกส่วนในระบบ
 *
 * ใช้ accessor profile_picture_url ของ User model เป็นหลัก
 * รองรับ LINE profile, uploaded profile, และ default avatar
 *
 * @props
 * - user: User|null - User model (ถ้าไม่ส่งจะใช้ Auth::user())
 * - size: string - ขนาด (xs, sm, md, lg, xl, 2xl) [default: md]
 * - class: string - CSS classes เพิ่มเติม
 * - showStatus: bool - แสดง online status [default: false]
 * - showBadge: bool - แสดง badge/icon [default: false]
 * - badge: string - ข้อความ badge
 * - clickable: bool - คลิกได้ [default: false]
 * - ring: bool - แสดงขอบ ring [default: true]
 * - ringColor: string - สี ring [default: white]
 *
 * @example
 * {{-- แสดง avatar ของผู้ใช้ปัจจุบัน --}}
 * <x-user-avatar />
 *
 * {{-- แสดง avatar ของผู้ใช้ที่ระบุ --}}
 * <x-user-avatar :user="$user" size="lg" />
 *
 * {{-- แสดง avatar พร้อม ring สีม่วง --}}
 * <x-user-avatar :user="$user" ring ringColor="purple" />
 *
 * @version 1.0.0
 * @author Claude AI
 */
--}}

@props([
    'user' => null,
    'size' => 'md',
    'class' => '',
    'showStatus' => false,
    'showBadge' => false,
    'badge' => '',
    'clickable' => false,
    'ring' => true,
    'ringColor' => 'white',
])

@php
    // ใช้ Auth::user() ถ้าไม่ได้ส่ง user มา
    $avatarUser = $user ?? Auth::user();

    // ดึง avatar URL จาก accessor (รองรับ LINE, uploaded, และ default)
    $avatarUrl = $avatarUser?->profile_picture_url ?? 'https://ui-avatars.com/api/?name=U&background=random&color=fff&size=200';

    // ชื่อสำหรับ alt text
    $userName = $avatarUser?->name ?? 'User';

    // กำหนดขนาดตาม size prop
    $sizeClasses = match($size) {
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-14 h-14 text-lg',
        'xl' => 'w-20 h-20 text-xl',
        '2xl' => 'w-32 h-32 text-3xl',
        '3xl' => 'w-40 h-40 text-4xl',
        default => 'w-10 h-10 text-base',
    };

    // กำหนด ring classes
    $ringClasses = $ring ? match($ringColor) {
        'white' => 'ring-2 ring-white dark:ring-gray-700',
        'purple' => 'ring-2 ring-purple-500',
        'blue' => 'ring-2 ring-blue-500',
        'green' => 'ring-2 ring-green-500',
        'gradient' => 'ring-2 ring-gradient-to-r from-purple-500 to-pink-500',
        'none' => '',
        default => 'ring-2 ring-white dark:ring-gray-700',
    } : '';

    // Status indicator size
    $statusSize = match($size) {
        'xs' => 'w-1.5 h-1.5',
        'sm' => 'w-2 h-2',
        'md' => 'w-2.5 h-2.5',
        'lg' => 'w-3 h-3',
        'xl' => 'w-4 h-4',
        '2xl' => 'w-5 h-5',
        '3xl' => 'w-6 h-6',
        default => 'w-2.5 h-2.5',
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'relative inline-block ' . $class]) }}
    @if($clickable) role="button" tabindex="0" @endif
>
    {{-- Avatar Image --}}
    <img
        src="{{ $avatarUrl }}"
        alt="{{ $userName }}"
        class="{{ $sizeClasses }} {{ $ringClasses }} rounded-full object-cover shadow-md"
        loading="lazy"
        onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($userName, 0, 1)) }}&background=6366f1&color=fff&size=200';"
    >

    {{-- Online Status Indicator --}}
    @if($showStatus)
        <span class="absolute bottom-0 right-0 {{ $statusSize }} bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
    @endif

    {{-- Badge --}}
    @if($showBadge && $badge)
        <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs font-bold text-white bg-red-500 rounded-full shadow-lg">
            {{ $badge }}
        </span>
    @endif
</div>
