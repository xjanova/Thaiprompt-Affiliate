@extends('layouts.admin-v3')

@section('title', 'การตลาดดูดวงอัตโนมัติ')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📣 การตลาดดูดวงอัตโนมัติ
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ตั้งแคมเปญ AI สร้างข้อความ + ส่งหาผู้ใช้เก่าอัตโนมัติตามเวลา
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.marketing.create') }}"
               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition inline-flex items-center">
                <span class="mr-2">+</span>
                สร้างแคมเปญใหม่
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">แคมเปญทั้งหมด</span>
                <span class="text-2xl">📋</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">กำลังทำงาน</span>
                <span class="text-2xl">🚀</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">ส่งเสร็จแล้ว</span>
                <span class="text-2xl">✅</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['completed']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-orange-100 text-sm">ส่งทั้งหมด</span>
                <span class="text-2xl">📨</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_sent']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-100 text-sm">แบบร่าง</span>
                <span class="text-2xl">📝</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['draft']) }}</div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อแคมเปญ หรือหัวข้อ..."
                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="status" class="px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 text-sm">
                <option value="">ทุกสถานะ</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>ตั้งเวลาแล้ว</option>
                <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>กำลังส่ง</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>ส่งเสร็จ</option>
                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>หยุดชั่วคราว</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">ค้นหา</button>
        </form>
    </div>

    {{-- Campaigns Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">แคมเปญ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เป้าหมาย</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ตารางส่ง</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ส่งแล้ว</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- แคมเปญ --}}
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $campaign->name }}
                                </div>
                                @if($campaign->topic)
                                    <div class="text-xs text-purple-600 dark:text-purple-400">หัวข้อ: {{ $campaign->topic }}</div>
                                @endif
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $campaign->use_ai_generate ? '🤖 AI สร้างข้อความ' : '✏️ กำหนดเอง' }}
                                </div>
                            </td>

                            {{-- เป้าหมาย --}}
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $campaign->target_label }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $campaign->target_platform === 'all' ? 'ทุก Platform' : strtoupper($campaign->target_platform) }}
                                    @if($campaign->target_limit)
                                        | สูงสุด {{ $campaign->target_limit }} คน
                                    @endif
                                </div>
                            </td>

                            {{-- ตารางส่ง --}}
                            <td class="px-4 py-3 text-center">
                                @if($campaign->schedule_type === 'once')
                                    <span class="text-xs">ส่งครั้งเดียว</span>
                                @elseif($campaign->schedule_type === 'daily')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">ทุกวัน</span>
                                @elseif($campaign->schedule_type === 'weekly')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">ทุกสัปดาห์</span>
                                @endif
                                @if($campaign->scheduled_at)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $campaign->scheduled_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($campaign->next_run_at)
                                    <div class="text-xs text-green-600 dark:text-green-400">
                                        ถัดไป: {{ $campaign->next_run_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-4 py-3 text-center">
                                @switch($campaign->status)
                                    @case('draft')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">📝 แบบร่าง</span>
                                        @break
                                    @case('scheduled')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">⏰ ตั้งเวลา</span>
                                        @break
                                    @case('sending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">🚀 กำลังส่ง</span>
                                        @break
                                    @case('completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">✅ ส่งเสร็จ</span>
                                        @break
                                    @case('paused')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">⏸ หยุดชั่วคราว</span>
                                        @break
                                    @case('cancelled')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">❌ ยกเลิก</span>
                                        @break
                                @endswitch
                                @if($campaign->last_error)
                                    <div class="text-xs text-red-500 mt-0.5" title="{{ $campaign->last_error }}">⚠️ error</div>
                                @endif
                            </td>

                            {{-- ส่งแล้ว --}}
                            <td class="px-4 py-3 text-center">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $campaign->sent_count }}</div>
                                @if($campaign->failed_count > 0)
                                    <div class="text-xs text-red-500">ล้มเหลว {{ $campaign->failed_count }}</div>
                                @endif
                                @if($campaign->last_run_at)
                                    <div class="text-xs text-gray-400">{{ $campaign->last_run_at->diffForHumans() }}</div>
                                @endif
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1 flex-wrap">
                                    {{-- แก้ไข --}}
                                    <a href="{{ route('admin.fortune.marketing.edit', $campaign) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition"
                                       title="แก้ไข">
                                        ✏️
                                    </a>

                                    {{-- AI สร้างข้อความ --}}
                                    @if($campaign->use_ai_generate)
                                        <form action="{{ route('admin.fortune.marketing.generate-message', $campaign) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="AI สร้างข้อความใหม่"
                                                    class="px-2 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded-lg text-xs transition">
                                                🤖
                                            </button>
                                        </form>
                                    @endif

                                    {{-- เปิดใช้งาน / ส่งทันที / หยุด --}}
                                    @if(in_array($campaign->status, ['draft', 'paused']))
                                        <form action="{{ route('admin.fortune.marketing.activate', $campaign) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="เปิดใช้งาน"
                                                    class="px-2 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-800/50 text-green-700 dark:text-green-300 rounded-lg text-xs transition">
                                                ▶️
                                            </button>
                                        </form>
                                    @endif

                                    @if($campaign->status === 'scheduled')
                                        <form action="{{ route('admin.fortune.marketing.send-now', $campaign) }}" method="POST" class="inline"
                                              onsubmit="return confirm('ยืนยันส่งทันที?')">
                                            @csrf
                                            <button type="submit" title="ส่งทันที"
                                                    class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs transition">
                                                📨
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.fortune.marketing.pause', $campaign) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="หยุดชั่วคราว"
                                                    class="px-2 py-1.5 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-800/50 text-orange-700 dark:text-orange-300 rounded-lg text-xs transition">
                                                ⏸
                                            </button>
                                        </form>
                                    @endif

                                    {{-- ลบ --}}
                                    <form action="{{ route('admin.fortune.marketing.destroy', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันลบแคมเปญนี้?')">
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
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">📣</div>
                                <p class="text-lg font-medium">ยังไม่มีแคมเปญการตลาด</p>
                                <p class="text-sm mt-1">คลิก "สร้างแคมเปญใหม่" เพื่อเริ่มต้น</p>
                                <a href="{{ route('admin.fortune.marketing.create') }}"
                                   class="mt-4 inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    + สร้างแคมเปญแรก
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($campaigns->hasPages())
        <div class="mt-6">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
