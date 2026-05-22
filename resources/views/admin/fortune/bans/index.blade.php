@extends('layouts.admin-v3')

@section('title', 'ระบบคุก — ระบบดูดวง')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ showBanForm: false }">
    {{-- Page Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🚫 ระบบคุก <span class="text-base font-normal text-gray-500 dark:text-gray-400">— ห้ามบอทคุย</span>
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    แบน user ไม่ให้บอทคุยด้วย (สแปม, ก่อกวน) — แอดมินยังคุยผ่าน Page Inbox / LINE OA ได้ตามปกติ
                </p>
            </div>
            <button type="button" @click="showBanForm = !showBanForm"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition text-sm font-medium">
                <span x-show="!showBanForm">🚫 แบน user ใหม่</span>
                <span x-show="showBanForm">✖ ปิดฟอร์ม</span>
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
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

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-red-500 to-red-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-100">ติดแบนอยู่</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_active']) }}</p>
                </div>
                <div class="text-4xl opacity-50">🚫</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">แบนถาวร</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['permanent']) }}</p>
                </div>
                <div class="text-4xl opacity-50">🔒</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-amber-100">แบนชั่วคราว</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['temporary']) }}</p>
                </div>
                <div class="text-4xl opacity-50">⏳</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-gray-500 to-gray-700 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-100">หมดอายุแล้ว</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_expired']) }}</p>
                </div>
                <div class="text-4xl opacity-50">📜</div>
            </div>
        </div>
    </div>

    {{-- Ban Form --}}
    <div x-show="showBanForm" x-transition class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6"
        x-data="{ durationType: 'permanent' }">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🚫 แบน user ใหม่</h2>
        <form method="POST" action="{{ route('admin.fortune.bans.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แพลตฟอร์ม *</label>
                    <select name="platform" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="facebook">🔵 Facebook</option>
                        <option value="line">💚 LINE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        User ID * <span class="text-xs text-gray-500">(PSID หรือ LINE userId)</span>
                    </label>
                    <input type="text" name="platform_user_id" required
                        value="{{ old('platform_user_id') }}"
                        placeholder="เช่น 28046354201620818"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อ (แสดงใน list) <span class="text-xs text-gray-500">— optional</span>
                    </label>
                    <input type="text" name="display_name"
                        value="{{ old('display_name') }}"
                        placeholder="ดึงจาก reading อัตโนมัติถ้าไม่กรอก"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระยะเวลา *</label>
                    <select name="duration_type" x-model="durationType" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="permanent">🔒 ถาวร</option>
                        <option value="minutes">⏱ นาที</option>
                        <option value="hours">⏰ ชั่วโมง</option>
                        <option value="days">📅 วัน</option>
                    </select>
                </div>
                <div x-show="durationType !== 'permanent'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวน *</label>
                    <input type="number" name="duration_value" min="1" max="100000"
                        value="{{ old('duration_value', 7) }}"
                        x-bind:required="durationType !== 'permanent'"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        เหตุผล <span class="text-xs text-gray-500">— สำหรับ audit</span>
                    </label>
                    <textarea name="reason" rows="2"
                        placeholder="เช่น สแปม, ก่อกวน, ขู่/คุกคาม..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('reason') }}</textarea>
                </div>
            </div>
            <div class="mt-4 flex gap-3 justify-end">
                <button type="button" @click="showBanForm = false"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg text-sm font-medium transition">
                    ยกเลิก
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    🚫 ยืนยันการแบน
                </button>
            </div>
        </form>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-4 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="active" @selected($filter === 'active')>🚫 ติดแบนอยู่</option>
                    <option value="expired" @selected($filter === 'expired')>📜 หมดอายุ</option>
                    <option value="all" @selected($filter === 'all')>ทั้งหมด</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">แพลตฟอร์ม</label>
                <select name="platform" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">ทุก Platform</option>
                    <option value="facebook" @selected($platform === 'facebook')>🔵 Facebook</option>
                    <option value="line" @selected($platform === 'line')>💚 LINE</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="ชื่อ, user id, เหตุผล..."
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                        🔍
                    </button>
                    <a href="{{ route('admin.fortune.bans.index') }}"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg text-sm font-medium transition">
                        รีเซ็ต
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Bans Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เหตุผล</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">พยายามทัก</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">แบนเมื่อ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($bans as $ban)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $ban->display_name ?: '(ไม่มีชื่อ)' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                                    {{ $ban->platform_user_id }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($ban->platform === 'facebook')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                        🔵 Facebook
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200">
                                        💚 LINE
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($ban->isPermanent())
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200">
                                        🔒 ถาวร
                                    </span>
                                @elseif ($ban->isActive())
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200">
                                            ⏳ {{ $ban->remainingHumanReadable() }}
                                        </span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            ถึง: {{ $ban->banned_until->format('Y-m-d H:i') }}
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                        📜 หมดอายุ
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                                {{ $ban->reason ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="text-gray-900 dark:text-white font-semibold">
                                    {{ number_format($ban->attempt_count) }} ครั้ง
                                </div>
                                @if ($ban->last_notified_at)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        แจ้งล่าสุด: {{ $ban->last_notified_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                <div>{{ $ban->created_at->format('Y-m-d') }}</div>
                                <div class="text-xs">{{ $ban->created_at->format('H:i') }}</div>
                                @if ($ban->bannedBy)
                                    <div class="text-xs text-gray-500 mt-1">โดย: {{ $ban->bannedBy->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <form method="POST" action="{{ route('admin.fortune.bans.destroy', $ban) }}"
                                    onsubmit="return confirm('ปลดแบน {{ $ban->platform }}:{{ $ban->platform_user_id }} ?');"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition">
                                        ✨ ปลดแบน
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">🕊️</div>
                                <p>ไม่มี user ที่ถูกแบนในสถานะนี้</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($bans->hasPages())
        <div class="mt-4">
            {{ $bans->links() }}
        </div>
    @endif
</div>
@endsection
