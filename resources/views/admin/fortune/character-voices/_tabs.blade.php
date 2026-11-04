{{-- 🎭 (2026-11-04) แท็บของระบบเสียงพากย์ตัวละคร — ตั้งค่า / ตัวละคร / คลังบทพูด / คลิปทั้งหมด --}}
@php
    $tabs = [
        'admin.fortune.character-voices.index' => '⚙️ ตั้งค่า & เลือกเสียง',
        'admin.fortune.character-voices.characters' => '🎭 ตัวละคร',
        'admin.fortune.character-voices.library' => '🗂️ คลังบทพูด & สร้างเสียง',
        'admin.fortune.character-voices.clips.index' => '🎧 คลิปทั้งหมด',
    ];
@endphp
<nav class="mb-5 -mx-1 flex gap-1 overflow-x-auto pb-1" aria-label="เมนูเสียงพากย์ตัวละคร">
    @foreach($tabs as $route => $label)
        <a href="{{ route($route) }}"
           @class([
               'whitespace-nowrap px-4 py-2 rounded-lg text-sm font-semibold transition',
               'bg-violet-600 text-white shadow' => request()->routeIs($route),
               'bg-white/70 text-gray-700 hover:bg-violet-50 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-700' => ! request()->routeIs($route),
           ])>{{ $label }}</a>
    @endforeach
</nav>
