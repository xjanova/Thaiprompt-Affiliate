@extends('layouts.admin-v3')

@section('title', 'เทคโอเวอร์ — ระบบดูดวง')

@section('content')
<div class="container mx-auto px-4 py-6"
     x-data="{
        showBan: false,
        banForm: { id: null, name: '', platform: 'facebook', userId: '', duration: 'permanent' },
        openBan(id, name, platform, userId) {
            this.banForm = { id: id, name: name || '(ไม่ระบุชื่อ)', platform: platform, userId: userId, duration: 'permanent' };
            this.showBan = true;
        },
        closeBan() { this.showBan = false; }
     }">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <a href="{{ route('admin.fortune.takeover.show', $reading) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg text-xs font-medium transition">
                                        เปิดคุย →
                                    </a>
                                    {{-- 🚫 (2026-05-23) ปุ่มแบนด่วน — เปิด modal เลือก duration --}}
                                    <button type="button"
                                            @click="openBan({{ $reading->id }}, @js($reading->facebook_user_name), @js($reading->platform ?? 'facebook'), @js($reading->platform_user_id ?: $reading->facebook_user_id))"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg text-xs font-medium transition"
                                            title="แบน user คนนี้">
                                        🚫
                                    </button>
                                </div>
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

    {{-- 🚫 (2026-05-23) Modal แบน user — เลือก preset duration --}}
    <div x-show="showBan" x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
         @keydown.escape.window="closeBan()"
         @click.self="closeBan()">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             x-transition.scale.95.duration.150ms>
            {{-- Header --}}
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-5 py-4 text-white">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    🚫 แบน user คนนี้
                </h3>
                <p class="text-xs text-red-100 mt-1">
                    บอทจะไม่ตอบ user คนนี้อีก (admin ยังคุยผ่าน Page Inbox / LINE OA ได้)
                </p>
            </div>

            {{-- Body --}}
            <form method="POST" :action="'{{ url('admin/fortune/takeover') }}/' + banForm.id + '/ban'">
                @csrf
                <input type="hidden" name="from" value="index">

                <div class="p-5 space-y-4">
                    {{-- User info --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-sm">
                        <div class="font-semibold text-gray-900 dark:text-white" x-text="banForm.name"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5 flex items-center gap-1.5 flex-wrap">
                            <span x-text="banForm.platform === 'line' ? '💚 LINE' : '🔵 Facebook'"></span>
                            <span class="opacity-70">•</span>
                            <span x-text="banForm.userId"></span>
                        </div>
                    </div>

                    {{-- Duration preset --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ระยะเวลาแบน
                        </label>
                        <div class="grid grid-cols-1 gap-2">
                            <template x-for="opt in [
                                {value: '10m', label: '⏱ 10 นาที — เตือนสั้นๆ', danger: false},
                                {value: '1h', label: '⏰ 1 ชั่วโมง', danger: false},
                                {value: '24h', label: '📅 24 ชั่วโมง', danger: false},
                                {value: '7d', label: '🗓️ 7 วัน', danger: false},
                                {value: 'permanent', label: '🔒 ถาวร', danger: true}
                            ]" :key="opt.value">
                                <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border-2 cursor-pointer transition"
                                       :class="banForm.duration === opt.value
                                            ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-500'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-700'">
                                    <input type="radio" name="duration" :value="opt.value" x-model="banForm.duration"
                                           class="text-red-600 focus:ring-red-500">
                                    <span class="text-sm"
                                          :class="opt.danger ? 'font-semibold text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-gray-100'"
                                          x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-5 py-3 flex items-center justify-end gap-2">
                    <button type="button" @click="closeBan()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg text-sm font-medium transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                        🚫 ยืนยันแบน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
