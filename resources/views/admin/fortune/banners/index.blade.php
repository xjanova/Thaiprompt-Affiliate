@extends('layouts.admin-v3')

@section('title', 'จัดการแบนเนอร์ DM')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            🖼️ จัดการแบนเนอร์ DM
        </h1>
        <a href="{{ route('admin.fortune.banners.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow transition">
            ➕ อัพโหลดแบนเนอร์ใหม่
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- ⚙️ Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚙️ ตั้งค่าระบบแบนเนอร์</h3>

        <form action="{{ route('admin.fortune.banners.settings') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- Master toggle --}}
                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                    <input type="checkbox" name="enable_dm_banner" value="1"
                           @checked($settings->enable_dm_banner ?? false)
                           class="w-5 h-5 text-blue-600 rounded">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">เปิดใช้งานระบบแบนเนอร์</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Master switch — ปิดทั้งระบบ</div>
                    </div>
                </label>

                {{-- Strategy --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        กลยุทธ์การเลือกแบนเนอร์
                    </label>
                    <select name="banner_pick_strategy"
                            class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="rotation" @selected(($settings->banner_pick_strategy ?? 'rotation') === 'rotation')>
                            🔄 วนรอบ (rotation) — ใช้ทุกแบนเนอร์เท่าๆ กัน
                        </option>
                        <option value="random" @selected(($settings->banner_pick_strategy ?? '') === 'random')>
                            🎲 สุ่ม (random) — สุ่มจริงทุกครั้ง
                        </option>
                        <option value="sequential" @selected(($settings->banner_pick_strategy ?? '') === 'sequential')>
                            📋 ตามลำดับ (sequential) — ใช้แบนเนอร์แรกตาม sort
                        </option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <label class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                    <input type="checkbox" name="banner_send_on_reaction" value="1"
                           @checked($settings->banner_send_on_reaction ?? true)
                           class="w-4 h-4 text-blue-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">👍 ส่งเมื่อกด reaction</span>
                </label>

                <label class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                    <input type="checkbox" name="banner_send_on_comment" value="1"
                           @checked($settings->banner_send_on_comment ?? true)
                           class="w-4 h-4 text-blue-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">💬 ส่งเมื่อ comment</span>
                </label>

                <label class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                    <input type="checkbox" name="banner_send_on_welcome" value="1"
                           @checked($settings->banner_send_on_welcome ?? true)
                           class="w-4 h-4 text-blue-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">🤝 ส่งเมื่อทักครั้งแรก</span>
                </label>
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
                💾 บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- Banner List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            🖼️ แบนเนอร์ทั้งหมด ({{ $banners->count() }})
        </h3>

        @if($banners->isEmpty())
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p class="mb-4">ยังไม่มีแบนเนอร์</p>
                <a href="{{ route('admin.fortune.banners.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                    ➕ อัพโหลดแบนเนอร์แรก
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($banners as $banner)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden flex flex-col">
                        {{-- รูป --}}
                        <div class="relative bg-gray-100 dark:bg-gray-900 aspect-video">
                            <img src="{{ $banner->image_url }}"
                                 alt="{{ $banner->name }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                            @if(! $banner->is_active)
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                    <span class="text-white font-bold">ปิดใช้งาน</span>
                                </div>
                            @endif
                            <div class="absolute top-2 left-2 px-2 py-0.5 rounded bg-black/60 text-white text-xs">
                                #{{ $banner->sort_order }}
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="p-4 flex-1 flex flex-col">
                            <div class="font-semibold text-gray-900 dark:text-white mb-1">
                                {{ $banner->name }}
                            </div>
                            @if($banner->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ Str::limit($banner->description, 60) }}
                                </div>
                            @endif

                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                📐 {{ $banner->width }}×{{ $banner->height }}
                                • 💾 {{ round(($banner->file_size ?? 0) / 1024) }} KB
                                • 📤 ส่งไปแล้ว {{ number_format($banner->send_count) }} ครั้ง
                            </div>

                            <div class="flex gap-2 mt-auto">
                                <a href="{{ route('admin.fortune.banners.edit', $banner) }}"
                                   class="flex-1 text-center px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 rounded">
                                    ✏️ แก้ไข
                                </a>
                                <form action="{{ route('admin.fortune.banners.toggle', $banner) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-3 py-1.5 text-xs {{ $banner->is_active ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800' : 'bg-green-100 hover:bg-green-200 text-green-800' }} rounded">
                                        {{ $banner->is_active ? '⏸️ ปิด' : '▶️ เปิด' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.fortune.banners.destroy', $banner) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('ลบแบนเนอร์นี้?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-full px-3 py-1.5 text-xs bg-red-100 hover:bg-red-200 text-red-800 rounded">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
