{{-- หมวดหมู่ทำนายฝัน --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">📂 หมวดหมู่ทำนายฝัน</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">จัดการหมวดหมู่สำหรับจัดกลุ่มสัญลักษณ์ฝัน</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ← พจนานุกรม
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.dream.categories.create') }}"
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition shadow-lg">
                ➕ เพิ่มหมวดหมู่
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- ตาราง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลำดับ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมวดหมู่</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สี</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สัญลักษณ์</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เปิดใช้งาน</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $category->sort_order }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ $category->icon }}</span>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $category->name_th }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg border border-gray-200 dark:border-gray-600"
                                         style="background-color: {{ $category->color_hex }};"></div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $category->color_hex }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $category->active_symbols_count }}</span>
                                <span class="text-xs text-gray-400"> / {{ $category->dream_symbols_count }}</span>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ $category->active_symbols_count }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($category->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">✅ เปิด</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">⏸️ ปิด</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.categories.edit', $category) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition">
                                        ✏️
                                    </a>
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.categories.destroy', $category) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ลบหมวดหมู่ «{{ $category->name_th }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg text-xs transition">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบหมวดหมู่
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($categories->hasPages())
        <div class="mt-6">{{ $categories->links() }}</div>
    @endif
</div>
@endsection
