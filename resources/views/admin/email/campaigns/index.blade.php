@extends('layouts.admin-v3')

@section('title', 'จัดการแคมเปญอีเมล')

@section('content')
{{-- ระบบจัดการแคมเปญอีเมล --}}
<div class="space-y-6" x-data="campaignManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📢</span>
                <span>แคมเปญอีเมล</span>
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                จัดการแคมเปญการส่งอีเมลแบบ Bulk
            </p>
        </div>
        <a href="{{ route('admin.email.campaigns.create') }}"
           class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างแคมเปญใหม่
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ทั้งหมด</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statusCounts['all']) }}</p>
                </div>
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <span class="text-xl">📧</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-yellow-200/50 dark:border-yellow-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ฉบับร่าง</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($statusCounts['draft']) }}</p>
                </div>
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <span class="text-xl">📝</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-blue-200/50 dark:border-blue-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">กำหนดเวลา</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($statusCounts['scheduled']) }}</p>
                </div>
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <span class="text-xl">⏰</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-purple-200/50 dark:border-purple-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">กำลังส่ง</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($statusCounts['sending']) }}</p>
                </div>
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <span class="text-xl">📤</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-green-200/50 dark:border-green-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ส่งเสร็จ</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($statusCounts['sent']) }}</p>
                </div>
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <span class="text-xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-red-200/50 dark:border-red-700/50 hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">ยกเลิก</p>
                    <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($statusCounts['cancelled']) }}</p>
                </div>
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                    <span class="text-xl">❌</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50">
        <form action="{{ route('admin.email.campaigns.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="🔍 ค้นหาชื่อหรือหัวเรื่อง..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
            </div>

            {{-- Status Filter --}}
            <select name="status"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                <option value="">ทุกสถานะ</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>กำหนดเวลา</option>
                <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>กำลังส่ง</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>ส่งเสร็จ</option>
                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>หยุดชั่วคราว</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>

            {{-- Buttons --}}
            <button type="submit"
                    class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
                ค้นหา
            </button>
            <a href="{{ route('admin.email.campaigns.index') }}"
               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
                รีเซ็ต
            </a>
        </form>
    </div>

    {{-- Campaigns Table --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/80 dark:bg-gray-900/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            แคมเปญ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้รับ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ส่งแล้ว
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            เปิดอ่าน
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            กำหนดเวลา
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Campaign Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <a href="{{ route('admin.email.campaigns.show', $campaign) }}"
                                           class="font-semibold text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                            {{ $campaign->name }}
                                        </a>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ Str::limit($campaign->subject, 50) }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                            โดย {{ $campaign->creator->name }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                @php
                                    $statusConfig = [
                                        'draft' => ['label' => 'ฉบับร่าง', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
                                        'scheduled' => ['label' => 'กำหนดเวลา', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
                                        'sending' => ['label' => 'กำลังส่ง', 'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
                                        'sent' => ['label' => 'ส่งเสร็จ', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
                                        'paused' => ['label' => 'หยุดชั่วคราว', 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'],
                                        'cancelled' => ['label' => 'ยกเลิก', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
                                        'failed' => ['label' => 'ล้มเหลว', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
                                    ];
                                    $config = $statusConfig[$campaign->status] ?? ['label' => $campaign->status, 'class' => 'bg-gray-100 text-gray-800'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $config['class'] }}">
                                    {{ $config['label'] }}
                                </span>
                            </td>

                            {{-- Recipients --}}
                            <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white font-semibold">
                                {{ number_format($campaign->total_recipients) }}
                            </td>

                            {{-- Sent --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($campaign->sent_count) }}
                                </div>
                                @if($campaign->total_recipients > 0)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format(($campaign->sent_count / $campaign->total_recipients) * 100, 1) }}%
                                    </div>
                                @endif
                            </td>

                            {{-- Opened --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($campaign->opened_count) }}
                                </div>
                                @if($campaign->delivered_count > 0)
                                    <div class="text-xs text-green-600 dark:text-green-400">
                                        {{ number_format(($campaign->opened_count / $campaign->delivered_count) * 100, 1) }}%
                                    </div>
                                @endif
                            </td>

                            {{-- Scheduled At --}}
                            <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                @if($campaign->scheduled_at)
                                    <div>{{ $campaign->scheduled_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $campaign->scheduled_at->format('H:i') }} น.</div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.email.campaigns.show', $campaign) }}"
                                       class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-all"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if($campaign->isEditable())
                                        <a href="{{ route('admin.email.campaigns.edit', $campaign) }}"
                                           class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition-all"
                                           title="แก้ไข">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    @if($campaign->isDeletable())
                                        <form action="{{ route('admin.email.campaigns.destroy', $campaign) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบแคมเปญนี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all"
                                                    title="ลบ">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-6xl mb-4">📭</span>
                                    <p class="text-gray-600 dark:text-gray-400 text-lg">ยังไม่มีแคมเปญ</p>
                                    <p class="text-gray-500 dark:text-gray-500 text-sm mt-2">เริ่มสร้างแคมเปญแรกของคุณเลย!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Campaign Manager Component
 * จัดการแคมเปญอีเมล
 */
function campaignManager() {
    return {
        // Properties
        loading: false,

        // Methods
        init() {
            console.log('Campaign Manager initialized');
        }
    }
}
</script>
@endpush
@endsection
