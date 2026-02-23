{{-- ผลทำนายฝัน — รายการ --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">📋 ผลทำนายฝัน</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">ดูผลทำนายฝัน AI ทั้งหมดจากผู้ใช้</p>
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← พจนานุกรม
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">ทั้งหมด</span>
                <span class="text-2xl">💭</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">วันนี้</span>
                <span class="text-2xl">📅</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['today']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">ใช้ฟรี</span>
                <span class="text-2xl">🎁</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['free']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-amber-100 text-sm">Views รวม</span>
                <span class="text-2xl">👁️</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['views']) }}</div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- ค้นหา --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="🔍 ค้นหาตามเรื่องฝัน..."
                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                🔍 ค้นหา
            </button>
        </form>
    </div>

    {{-- ตาราง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เรื่องฝัน</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำสำคัญ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เลขเด็ด</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">AI</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Views</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($readings as $reading)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">#{{ $reading->id }}</td>
                            <td class="px-4 py-4">
                                <span class="text-sm text-gray-900 dark:text-white line-clamp-1">
                                    {{ Str::limit($reading->dream_description, 50) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ count($reading->matched_keywords ?? []) }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if(!empty($reading->lucky_numbers))
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @foreach(array_slice($reading->lucky_numbers, 0, 3) as $num)
                                            <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded text-xs font-mono font-bold">{{ $num }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                {{ $reading->ai_provider_used ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($reading->view_count) }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $reading->created_at->locale('th')->diffForHumans() }}
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.readings.show', $reading) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition">
                                        🔍
                                    </a>
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.readings.destroy', $reading) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ลบผลทำนาย #{{ $reading->id }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg text-xs transition">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มีผลทำนายฝัน
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($readings->hasPages())
        <div class="mt-6">{{ $readings->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
