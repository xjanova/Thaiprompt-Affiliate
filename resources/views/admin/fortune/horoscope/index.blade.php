{{-- resources/views/admin/fortune/horoscope/index.blade.php --}}
{{-- รายการแคมเปญโพสดวงรายวันอัตโนมัติ --}}

@extends('layouts.admin')

@section('title', 'ดวงรายวันอัตโนมัติ')

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 rounded-xl text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                🔮 ดวงรายวันอัตโนมัติ
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการแคมเปญ AI สร้างดวงรายวัน + โพสอัตโนมัติลง Facebook / LINE
            </p>
        </div>
        <a href="{{ route('admin.fortune.horoscope.create') }}"
           class="px-6 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition inline-flex items-center gap-2 shadow-lg">
            <span>+</span>
            <span>สร้างแคมเปญใหม่</span>
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- แคมเปญทั้งหมด --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">แคมเปญทั้งหมด</span>
                <span class="text-2xl">📋</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>

        {{-- กำลังทำงาน --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">กำลังทำงาน</span>
                <span class="text-2xl">✅</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>

        {{-- เนื้อหาวันนี้ --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">เนื้อหาวันนี้</span>
                <span class="text-2xl">🤖</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['contents_today']) }}</div>
        </div>

        {{-- โพสวันนี้ --}}
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-orange-100 text-sm">โพสวันนี้</span>
                <span class="text-2xl">📤</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['posts_today']) }}</div>
        </div>
    </div>

    {{-- Filter / Search --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <form method="GET" action="{{ route('admin.fortune.horoscope.index') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาแคมเปญ..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">-- สถานะทั้งหมด --</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>📝 แบบร่าง</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ กำลังทำงาน</option>
                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>⏸ หยุดชั่วคราว</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>❌ ยกเลิก</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.fortune.horoscope.index') }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Campaign Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            แคมเปญ
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            แพลตฟอร์ม
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            เวลาโพส
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            สถานะ
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            เนื้อหา / โพส
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            โพสล่าสุด
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- ชื่อแคมเปญ --}}
                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $campaign->name }}
                                </div>
                                @if($campaign->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ Str::limit($campaign->description, 60) }}
                                    </div>
                                @endif
                                @if($campaign->last_error)
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                        ⚠️ {{ Str::limit($campaign->last_error, 40) }}
                                    </div>
                                @endif
                            </td>

                            {{-- แพลตฟอร์ม --}}
                            <td class="px-4 py-4 text-center">
                                <div class="flex justify-center gap-1">
                                    @if($campaign->post_to_facebook)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300" title="Facebook">
                                            📘 FB
                                        </span>
                                    @endif
                                    @if($campaign->post_to_line)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300" title="LINE">
                                            💚 LINE
                                        </span>
                                    @endif
                                    @if(!$campaign->post_to_facebook && !$campaign->post_to_line)
                                        <span class="text-xs text-gray-400">ยังไม่ตั้ง</span>
                                    @endif
                                </div>
                            </td>

                            {{-- เวลาโพส --}}
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm font-mono text-gray-900 dark:text-white">
                                    {{ $campaign->schedule_time ?? '06:00' }}
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $campaign->timezone ?? 'Asia/Bangkok' }}
                                </div>
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-4 py-4 text-center">
                                @php $color = $campaign->status_color; @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    bg-{{ $color }}-100 text-{{ $color }}-800
                                    dark:bg-{{ $color }}-900/50 dark:text-{{ $color }}-300">
                                    @switch($campaign->status)
                                        @case('draft') 📝 @break
                                        @case('active') ✅ @break
                                        @case('paused') ⏸ @break
                                        @case('cancelled') ❌ @break
                                    @endswitch
                                    {{ $campaign->status_label }}
                                </span>
                            </td>

                            {{-- เนื้อหา / โพส --}}
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ number_format($campaign->contents_count) }} / {{ number_format($campaign->posts_count) }}
                                </span>
                            </td>

                            {{-- โพสล่าสุด --}}
                            <td class="px-4 py-4 text-center text-sm">
                                @if($campaign->last_posted_at)
                                    <span class="text-gray-900 dark:text-white">
                                        {{ $campaign->last_posted_at->format('d/m/Y') }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $campaign->last_posted_at->format('H:i') }}
                                    </div>
                                @else
                                    <span class="text-gray-400">ยังไม่เคยโพส</span>
                                @endif
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-1 flex-wrap">
                                    {{-- แก้ไข --}}
                                    <a href="{{ route('admin.fortune.horoscope.edit', $campaign) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition"
                                       title="แก้ไข">
                                        ✏️
                                    </a>

                                    {{-- ดูเนื้อหา --}}
                                    <a href="{{ route('admin.fortune.horoscope.content-history', $campaign) }}"
                                       class="px-2 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded-lg text-xs transition"
                                       title="ดูเนื้อหา">
                                        🤖
                                    </a>

                                    {{-- ดูประวัติโพส --}}
                                    <a href="{{ route('admin.fortune.horoscope.post-history', $campaign) }}"
                                       class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs transition"
                                       title="ประวัติโพส">
                                        📤
                                    </a>

                                    {{-- เปิด/หยุด --}}
                                    @if($campaign->status === 'active')
                                        <form action="{{ route('admin.fortune.horoscope.pause', $campaign) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="หยุดชั่วคราว"
                                                    class="px-2 py-1.5 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-800/50 text-yellow-700 dark:text-yellow-300 rounded-lg text-xs transition">
                                                ⏸
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.fortune.horoscope.activate', $campaign) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="เปิดใช้งาน"
                                                    class="px-2 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-800/50 text-green-700 dark:text-green-300 rounded-lg text-xs transition">
                                                ▶️
                                            </button>
                                        </form>
                                    @endif

                                    {{-- สร้างเนื้อหาทันที --}}
                                    <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ต้องการสร้างเนื้อหา AI ทันทีหรือไม่? อาจใช้เวลา 2-5 นาที')">
                                        @csrf
                                        <button type="submit" title="สร้างเนื้อหา AI ทันที"
                                                class="px-2 py-1.5 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:hover:bg-indigo-800/50 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs transition">
                                            ⚡
                                        </button>
                                    </form>

                                    {{-- โพสทันที --}}
                                    <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                                        @csrf
                                        <button type="submit" title="โพสทันที"
                                                class="px-2 py-1.5 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-800/50 text-orange-700 dark:text-orange-300 rounded-lg text-xs transition">
                                            🚀
                                        </button>
                                    </form>

                                    {{-- ลบ --}}
                                    <form action="{{ route('admin.fortune.horoscope.destroy', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันลบแคมเปญ {{ $campaign->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="ลบ"
                                                class="px-2 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-800/50 text-red-700 dark:text-red-300 rounded-lg text-xs transition">
                                            🗑
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400 dark:text-gray-500">
                                    <div class="text-4xl mb-3">🔮</div>
                                    <p class="text-lg font-medium">ยังไม่มีแคมเปญ</p>
                                    <p class="text-sm mt-1">สร้างแคมเปญใหม่เพื่อเริ่มโพสดวงรายวันอัตโนมัติ</p>
                                    <a href="{{ route('admin.fortune.horoscope.create') }}"
                                       class="inline-block mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                                        + สร้างแคมเปญแรก
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($campaigns->hasPages())
        <div class="mt-6">
            {{ $campaigns->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
