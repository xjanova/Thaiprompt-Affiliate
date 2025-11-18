@extends('layouts.admin-v3')

@section('title', 'จัดการโฆษณา POS')

@section('content')
<div class="space-y-6" x-data="{ showFilters: false }">
    <!-- Header with Actions -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-ad mr-3"></i>
                    จัดการโฆษณา POS
                </h1>
                <p class="text-purple-100">ควบคุมโฆษณาที่แสดงบนระบบ POS</p>
            </div>
            <a href="{{ route('admin.pos.advertisements.create') }}"
               class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i>
                สร้างโฆษณาใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">โฆษณาทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $advertisements->total() }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-full">
                    <i class="fas fa-bullhorn text-2xl text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">กำลังใช้งาน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $advertisements->where('is_active', true)->count() }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-4 rounded-full">
                    <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">หยุดใช้งาน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $advertisements->where('is_active', false)->count() }}</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 p-4 rounded-full">
                    <i class="fas fa-pause-circle text-2xl text-yellow-600 dark:text-yellow-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">ร้านค้าที่เชื่อมต่อ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stores->count() }}</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-full">
                    <i class="fas fa-store text-2xl text-purple-600 dark:text-purple-300"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <button @click="showFilters = !showFilters"
                class="flex items-center justify-between w-full text-left">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-filter mr-2 text-indigo-600"></i>
                ตัวกรอง
            </h3>
            <i class="fas fa-chevron-down transform transition-transform duration-200"
               :class="{ 'rotate-180': showFilters }"></i>
        </button>

        <div x-show="showFilters"
             x-collapse
             class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <form action="{{ route('admin.pos.advertisements.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-1"></i> ค้นหา
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ชื่อ, คำอธิบาย, โค้ด..."
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Store Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-store mr-1"></i> ร้านค้า
                    </label>
                    <select name="store_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทั้งหมด</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-layer-group mr-1"></i> ประเภท
                    </label>
                    <select name="type"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทั้งหมด</option>
                        <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>รูปภาพ</option>
                        <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>วิดีโอ</option>
                        <option value="html" {{ request('type') == 'html' ? 'selected' : '' }}>HTML</option>
                        <option value="promotion" {{ request('type') == 'promotion' ? 'selected' : '' }}>โปรโมชั่น</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-power-off mr-1"></i> สถานะ
                    </label>
                    <select name="is_active"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>หยุดใช้งาน</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex gap-3">
                    <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        ค้นหา
                    </button>
                    <a href="{{ route('admin.pos.advertisements.index') }}"
                       class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i>
                        รีเซ็ต
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Advertisements List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ตัวอย่าง
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            รายละเอียด
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถิติ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($advertisements as $ad)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <!-- Preview -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($ad->type === 'image' && $ad->image_url)
                                    <img src="{{ Storage::url($ad->image_url) }}"
                                         alt="{{ $ad->title }}"
                                         class="w-20 h-20 object-cover rounded-lg shadow-md">
                                @elseif($ad->type === 'video' && $ad->video_url)
                                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-play text-3xl text-white"></i>
                                    </div>
                                @elseif($ad->type === 'promotion')
                                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-percent text-3xl text-white"></i>
                                    </div>
                                @else
                                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-code text-3xl text-white"></i>
                                    </div>
                                @endif
                            </td>

                            <!-- Details -->
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $ad->title }}</p>
                                    @if($ad->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ Str::limit($ad->description, 60) }}</p>
                                    @endif
                                    @if($ad->store)
                                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                            <i class="fas fa-store mr-1"></i>{{ $ad->store->name }}
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $typeColors = [
                                        'image' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'video' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                        'html' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'promotion' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    ];
                                    $typeIcons = [
                                        'image' => 'fa-image',
                                        'video' => 'fa-video',
                                        'html' => 'fa-code',
                                        'promotion' => 'fa-tags',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $typeColors[$ad->type] ?? '' }}">
                                    <i class="fas {{ $typeIcons[$ad->type] ?? 'fa-question' }} mr-1"></i>
                                    {{ ucfirst($ad->type) }}
                                </span>
                            </td>

                            <!-- Stats -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-eye text-blue-500 w-4 mr-2"></i>
                                        <span class="text-gray-700 dark:text-gray-300">{{ number_format($ad->view_count) }}</span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-mouse-pointer text-green-500 w-4 mr-2"></i>
                                        <span class="text-gray-700 dark:text-gray-300">{{ number_format($ad->click_count) }}</span>
                                    </div>
                                    @if($ad->view_count > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            CTR: {{ number_format(($ad->click_count / $ad->view_count) * 100, 2) }}%
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.pos.advertisements.toggle-status', $ad) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                                            {{ $ad->is_active ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                            {{ $ad->is_active ? 'translate-x-6' : 'translate-x-1' }}">
                                        </span>
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.pos.advertisements.show', $ad) }}"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>
                                    <a href="{{ route('admin.pos.advertisements.preview', $ad) }}"
                                       class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                       title="ดูตัวอย่าง"
                                       target="_blank">
                                        <i class="fas fa-play-circle text-lg"></i>
                                    </a>
                                    <a href="{{ route('admin.pos.advertisements.edit', $ad) }}"
                                       class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
                                       title="แก้ไข">
                                        <i class="fas fa-edit text-lg"></i>
                                    </a>
                                    <form action="{{ route('admin.pos.advertisements.destroy', $ad) }}"
                                          method="POST"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบโฆษณานี้?')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                title="ลบ">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-ad text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบโฆษณา</p>
                                    <a href="{{ route('admin.pos.advertisements.create') }}"
                                       class="mt-4 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        สร้างโฆษณาใหม่
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($advertisements->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $advertisements->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-xl p-6 text-white">
        <h3 class="text-lg font-semibold mb-4">การดำเนินการด่วน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.pos.advertisements.analytics') }}"
               class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200">
                <i class="fas fa-chart-line text-2xl mb-2"></i>
                <p class="font-semibold">ดูสถิติโฆษณา</p>
            </a>
            <a href="{{ route('admin.pos.advertisements.create') }}"
               class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200">
                <i class="fas fa-plus-circle text-2xl mb-2"></i>
                <p class="font-semibold">สร้างโฆษณาใหม่</p>
            </a>
            <button onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')"
                    class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200 text-left">
                <i class="fas fa-download text-2xl mb-2"></i>
                <p class="font-semibold">ส่งออกรายงาน</p>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Animate stats on load
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from('.grid > div', {
            opacity: 0,
            y: 20,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out'
        });
    });
</script>
@endpush
@endsection
