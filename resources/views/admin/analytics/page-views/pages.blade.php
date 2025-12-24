{{--
/**
 * หน้ารายละเอียด Pages Analytics
 */
--}}

@extends('layouts.admin')

@section('title', 'Page Views - รายละเอียดหน้า')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">📄</span>
                รายละเอียดหน้าที่เข้าชม
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                สถิติการเข้าชมแยกตามแต่ละหน้า
            </p>
        </div>

        <div class="mt-4 md:mt-0 flex items-center gap-4">
            <select onchange="window.location.href = '?period=' + this.value"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg">
                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>วันนี้</option>
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 วันล่าสุด</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 วันล่าสุด</option>
            </select>

            <a href="{{ route('admin.analytics.page-views.index') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Path</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Route</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Views</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unique</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($pages as $page)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->path }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $page->route_name ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($page->views) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ number_format($page->unique_visitors) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ round($page->avg_response_time, 0) }} ms</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            ไม่มีข้อมูล
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $pages->appends(['period' => $period])->links() }}
        </div>
    </div>
</div>
@endsection
