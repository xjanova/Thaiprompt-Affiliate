@extends('layouts.admin')

@section('title', 'เทคโอเวอร์ — ระบบดูดวง')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Page Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🎯 เทคโอเวอร์ <span class="text-base font-normal text-gray-500 dark:text-gray-400">— แม่หมอคุยแทน AI</span>
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    จัดการบทสนทนา — กดเทคโอเวอร์เพื่อให้ AI หยุด และแอดมินคุยเองได้ทั้ง LINE และ Facebook
                </p>
            </div>
            <a href="{{ route('admin.fortune.settings.index') }}#takeover"
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition text-sm font-medium">
                ⚙️ ตั้งค่าระบบเทคโอเวอร์
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">กำลังเทคโอเวอร์</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_taken_over']) }}</p>
                </div>
                <div class="text-4xl opacity-50">🎯</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">บทสนทนา Active (7 วัน)</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_active']) }}</p>
                </div>
                <div class="text-4xl opacity-50">💬</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-emerald-100">เทคโอเวอร์วันนี้</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['takeovers_today']) }}</p>
                </div>
                <div class="text-4xl opacity-50">📈</div>
            </div>
        </div>
    </div>

    {{-- Settings Summary --}}
    @if(! $settings->isTakeoverEnabled())
    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg">
        <div class="flex items-start gap-3">
            <div class="text-2xl">⚠️</div>
            <div class="flex-1">
                <p class="font-semibold text-amber-900 dark:text-amber-200">ระบบเทคโอเวอร์ถูกปิดอยู่</p>
                <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                    เปิดใช้งานที่ <a href="{{ route('admin.fortune.settings.index') }}#takeover" class="underline font-medium">ตั้งค่าระบบดูดวง → ระบบเทคโอเวอร์</a>
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-4 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="active" @selected($filter === 'active')>กำลังสนทนา (7 วัน)</option>
                    <option value="taken_over" @selected($filter === 'taken_over')>🎯 ถูกเทคโอเวอร์</option>
                    <option value="all" @selected($filter === 'all')>ทั้งหมด</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                <select name="platform" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">ทุก Platform</option>
                    <option value="line" @selected($platform === 'line')>💚 LINE</option>
                    <option value="facebook" @selected($platform === 'facebook')>🔵 Facebook</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="ชื่อ, user id, bill reference..."
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                        🔍
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Readings Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Takeover</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">อัพเดตล่าสุด</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($readings as $reading)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            x-data="{ isActive: {{ $reading->isAdminTakenOver() ? 'true' : 'false' }} }">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ Str::limit($reading->platform_user_id ?: $reading->facebook_user_id, 20) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($reading->platform === 'line')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        💚 LINE
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                        🔵 Facebook
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @php
                                    $statusLabel = match($reading->conversation_status) {
                                        'new' => ['เริ่มต้น', 'gray'],
                                        'awaiting_confirmation' => ['รอยืนยัน', 'yellow'],
                                        'basic_done' => ['พื้นฐานเสร็จ', 'blue'],
                                        'collecting_birthdate' => ['กำลังเก็บวันเกิด', 'yellow'],
                                        'collecting_questions' => ['กำลังเก็บคำถาม', 'yellow'],
                                        'collecting_tarot' => ['กำลังเก็บไพ่', 'yellow'],
                                        'pending_payment' => ['รอชำระเงิน', 'orange'],
                                        'paid' => ['จ่ายแล้ว', 'purple'],
                                        'completed' => ['เสร็จสิ้น', 'green'],
                                        default => [$reading->conversation_status, 'gray'],
                                    };
                                    $color = $statusLabel[1];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/50 dark:text-{{ $color }}-300">
                                    {{ $statusLabel[0] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="isActive">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-900 dark:bg-purple-900/50 dark:text-purple-200">
                                        🎯 เทคโอเวอร์อยู่
                                        <span class="text-[10px] opacity-75">({{ $reading->takeoverRemainingMinutes() }}m)</span>
                                    </span>
                                </template>
                                <template x-if="! isActive">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        🤖 AI ทำงาน
                                    </span>
                                </template>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $reading->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.fortune.takeover.show', $reading) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg text-xs font-medium transition">
                                    เปิดคุย →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-5xl mb-2">🔍</div>
                                <p>ไม่พบบทสนทนา</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $readings->links() }}
    </div>
</div>
@endsection
