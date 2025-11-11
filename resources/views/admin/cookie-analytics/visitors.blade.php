@extends('layouts.admin')

@section('title', 'รายการผู้เข้าชม - Cookie Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-400 dark:to-cyan-400 bg-clip-text text-transparent">
                        รายการผู้เข้าชม
                    </h1>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">
                        ติดตามและวิเคราะห์พฤติกรรมผู้เข้าชมเว็บไซต์
                    </p>
                </div>
                <a href="{{ route('admin.cookie-analytics.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 text-slate-700 dark:text-slate-200 hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับไปแดชบอร์ด
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300">
            <form method="GET" action="{{ route('admin.cookie-analytics.visitors') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Device Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            ประเภทอุปกรณ์
                        </label>
                        <select name="device_type" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                            <option value="">ทั้งหมด</option>
                            <option value="desktop" {{ request('device_type') == 'desktop' ? 'selected' : '' }}>Desktop</option>
                            <option value="mobile" {{ request('device_type') == 'mobile' ? 'selected' : '' }}>Mobile</option>
                            <option value="tablet" {{ request('device_type') == 'tablet' ? 'selected' : '' }}>Tablet</option>
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ประเทศ
                        </label>
                        <input type="text" name="country" value="{{ request('country') }}"
                               placeholder="เช่น Thailand, US"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                    </div>

                    <!-- Referrer Filter -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Referrer
                        </label>
                        <input type="text" name="referrer" value="{{ request('referrer') }}"
                               placeholder="เช่น google.com"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 font-medium hover:scale-105">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            กรองข้อมูล
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">ผู้เข้าชมทั้งหมด</p>
                        <p class="text-3xl font-bold mt-2">{{ number_format($visitors->total()) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 dark:from-cyan-600 dark:to-cyan-700 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 animate-fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-cyan-100 text-sm font-medium">หน้านี้</p>
                        <p class="text-3xl font-bold mt-2">{{ number_format($visitors->count()) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 animate-fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100 text-sm font-medium">กำลังกรอง</p>
                        <p class="text-3xl font-bold mt-2">{{ request()->hasAny(['device_type', 'country', 'referrer']) ? 'ใช่' : 'ไม่' }}</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visitors Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden border border-slate-200 dark:border-slate-700 transition-all duration-300 animate-slide-up">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700 dark:to-slate-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                ผู้เข้าชม
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                อุปกรณ์ & เบราว์เซอร์
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                ที่ตั้ง
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                การใช้งาน
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                เวลา
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($visitors as $visitor)
                        <tr class="hover:bg-blue-50/50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                            <!-- Visitor Info -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center text-white font-bold shadow-lg">
                                        {{ substr($visitor->session_id ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ $visitor->user ? $visitor->user->name : 'Guest' }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                            {{ Str::limit($visitor->session_id, 16) }}
                                        </div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">
                                            IP: {{ $visitor->ip_address }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Device & Browser -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center text-sm text-slate-900 dark:text-slate-100">
                                        @if($visitor->device_type == 'desktop')
                                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        @elseif($visitor->device_type == 'mobile')
                                            <svg class="w-4 h-4 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        @endif
                                        <span class="capitalize">{{ $visitor->device_type ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ $visitor->browser ?? 'Unknown Browser' }}
                                    </div>
                                </div>
                            </td>

                            <!-- Location -->
                            <td class="px-6 py-4">
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="text-slate-900 dark:text-slate-100">
                                        {{ $visitor->country ?? 'Unknown' }}
                                    </span>
                                </div>
                                @if($visitor->referrer_domain)
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                    {{ Str::limit($visitor->referrer_domain, 25) }}
                                </div>
                                @endif
                            </td>

                            <!-- Usage Stats -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center text-sm text-slate-900 dark:text-slate-100">
                                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span>{{ $visitor->page_views ?? 0 }} หน้า</span>
                                    </div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ gmdate('i:s', $visitor->session_duration ?? 0) }} นาที
                                    </div>
                                </div>
                            </td>

                            <!-- Time -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-slate-100">
                                    {{ $visitor->last_activity_at ? $visitor->last_activity_at->format('d/m/Y') : '-' }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $visitor->last_activity_at ? $visitor->last_activity_at->format('H:i') : '-' }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="{{ route('admin.cookie-analytics.visitor-detail', $visitor->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white text-sm font-medium rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-lg font-medium">ไม่พบข้อมูลผู้เข้าชม</p>
                                    <p class="text-sm mt-1">ลองเปลี่ยนตัวกรองหรือรอผู้เข้าชมใหม่</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($visitors->hasPages())
            <div class="bg-slate-50 dark:bg-slate-700/50 px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $visitors->links() }}
            </div>
            @endif
        </div>

    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
}

.animate-slide-up {
    animation: slide-up 0.6s ease-out forwards;
}
</style>
@endsection
