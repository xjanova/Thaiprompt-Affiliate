@extends('layouts.admin-v3')

@section('title', 'การทำภารกิจทั้งหมด')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                📋 การทำภารกิจทั้งหมด
            </h1>
            <p class="text-gray-600 dark:text-gray-400">ดูและจัดการการทำภารกิจของผู้ใช้</p>
        </div>
        <a href="{{ route('admin.video-missions.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ Dashboard
        </a>
    </div>

    {{-- Filters --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 mb-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหาผู้ใช้</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อหรืออีเมล..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="watching" {{ request('status') == 'watching' ? 'selected' : '' }}>กำลังดู</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>ทำเสร็จ</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>ยืนยันแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>หมดเวลา</option>
                </select>
            </div>
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภารกิจ</label>
                <select name="mission_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($missions as $m)
                    <option value="{{ $m->id }}" {{ request('mission_id') == $m->id ? 'selected' : '' }}>{{ $m->display_title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="suspicious" value="1" {{ request('suspicious') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">เฉพาะน่าสงสัย</span>
                </label>
            </div>
            <button type="submit" class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium transition">
                🔍 ค้นหา
            </button>
        </form>
    </div>

    {{-- Completions Table --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ภารกิจ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ดู %</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Anti-Cheat</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($completions as $completion)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition {{ $completion->is_suspicious ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-lg">
                                    {{ substr($completion->user->name ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $completion->user->name ?? 'Unknown' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $completion->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $completion->mission->display_title ?? 'ลบแล้ว' }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="inline-flex items-center gap-2">
                                <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full" style="width: {{ min($completion->watch_percentage, 100) }}%"></div>
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $completion->watch_percentage }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $completion->status === 'verified' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                {{ $completion->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : '' }}
                                {{ $completion->status === 'watching' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                {{ $completion->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : '' }}
                                {{ in_array($completion->status, ['cancelled', 'expired']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                            ">
                                {{ $completion->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-2 text-sm">
                                @if($completion->is_suspicious)
                                <span class="text-red-500" title="น่าสงสัย">⚠️</span>
                                @else
                                <span class="text-green-500" title="ปกติ">✅</span>
                                @endif
                                <span class="text-gray-500 dark:text-gray-400" title="Tab Switches">
                                    📑{{ $completion->tab_switch_count ?? 0 }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400" title="Heartbeats">
                                    💓{{ $completion->heartbeat_count ?? 0 }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $completion->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.video-missions.completion', $completion) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-lg transition"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if($completion->status === 'completed')
                                <form action="{{ route('admin.video-missions.completion.verify', $completion) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/50 rounded-lg transition"
                                            title="ยืนยัน">
                                        ✅
                                    </button>
                                </form>
                                <form action="{{ route('admin.video-missions.completion.reject', $completion) }}" method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการปฏิเสธ?')">
                                    @csrf
                                    <input type="hidden" name="reason" value="Manual rejection">
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50 rounded-lg transition"
                                            title="ปฏิเสธ">
                                        ❌
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="text-5xl mb-4">📭</div>
                            <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูล</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($completions->hasPages())
    <div class="mt-6">
        {{ $completions->links() }}
    </div>
    @endif
</div>
@endsection
