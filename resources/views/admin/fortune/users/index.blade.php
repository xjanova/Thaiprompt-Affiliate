@extends('layouts.admin')

@section('title', 'ผู้ใช้ดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortuneUsers()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                👥 ผู้ใช้ดูดวง
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการผู้ใช้ที่เคยดูดวง ส่งข้อความ เพิ่มเครดิต
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <button @click="showBroadcast = true"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">📢</span>
                ส่งข้อความถึงทุกคน
            </button>
            <button @click="showSendMessage = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">💬</span>
                ส่งข้อความรายคน
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">ผู้ใช้ทั้งหมด</span>
                <span class="text-2xl">👥</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_users']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-indigo-100 text-sm">Facebook</span>
                <span class="text-2xl">📘</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['facebook_users']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">LINE</span>
                <span class="text-2xl">🟢</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['line_users']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-orange-100 text-sm">ลูกค้าจ่ายเงิน</span>
                <span class="text-2xl">💰</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['paying_users']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">รายได้รวม</span>
                <span class="text-2xl">💵</span>
            </div>
            <div class="text-2xl font-bold">฿{{ number_format($stats['total_revenue'], 0) }}</div>
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

    {{-- Send Message Form (เฉพาะรายคน) --}}
    <div x-show="showSendMessage" x-cloak
         class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-2 border-blue-200 dark:border-blue-800">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            💬 ส่งข้อความรายคน
        </h2>
        <form action="{{ route('admin.fortune.users.send-message') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform *</label>
                    <select name="platform" x-model="sendPlatform"
                            class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                        <option value="facebook">Facebook Messenger</option>
                        <option value="line">LINE</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID *</label>
                    <input type="text" name="facebook_user_id" x-model="sendUserId" required
                           placeholder="Facebook/LINE User ID"
                           class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความ *</label>
                <textarea name="message" rows="4" required maxlength="2000"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                          class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    ส่งข้อความ
                </button>
                <button type="button" @click="showSendMessage = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

    {{-- Broadcast Form --}}
    <div x-show="showBroadcast" x-cloak
         class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-2 border-purple-200 dark:border-purple-800">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            📢 ส่งข้อความถึงทุกคน (Broadcast)
        </h2>
        <form action="{{ route('admin.fortune.users.broadcast') }}" method="POST"
              onsubmit="return confirm('ยืนยันส่งข้อความ Broadcast? จะส่งไปทุกคนตามเงื่อนไขที่เลือก')">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ช่องทาง</label>
                    <select name="platform"
                            class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-purple-500">
                        <option value="all">ทุกช่องทาง</option>
                        <option value="facebook">Facebook เท่านั้น</option>
                        <option value="line">LINE เท่านั้น</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มเป้าหมาย</label>
                    <select name="target"
                            class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-purple-500">
                        <option value="all">ทุกคนที่เคยดูดวง</option>
                        <option value="paid">เฉพาะลูกค้าจ่ายเงิน</option>
                        <option value="recent">ดูดวงภายใน 7 วันล่าสุด</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความ *</label>
                <textarea name="message" rows="4" required maxlength="2000"
                          placeholder="พิมพ์ข้อความ Broadcast..."
                          class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    ส่ง Broadcast
                </button>
                <button type="button" @click="showBroadcast = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

    {{-- Search & Filter --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <form method="GET" action="{{ route('admin.fortune.users.index') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหา User ID หรือชื่อ..."
                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3 items-center">
                <select name="platform" class="px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">ทุก Platform</option>
                    <option value="facebook" {{ request('platform') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                    <option value="line" {{ request('platform') === 'line' ? 'selected' : '' }}>LINE</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                    <input type="checkbox" name="paid_only" value="1" {{ request('paid_only') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                    จ่ายเงินแล้ว
                </label>
                <select name="sort" class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 text-sm">
                    <option value="last_reading_at" {{ request('sort') === 'last_reading_at' ? 'selected' : '' }}>ล่าสุด</option>
                    <option value="total_readings" {{ request('sort') === 'total_readings' ? 'selected' : '' }}>จำนวนดู</option>
                    <option value="total_spent" {{ request('sort') === 'total_spent' ? 'selected' : '' }}>ยอดจ่าย</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    ค้นหา
                </button>
                @if(request()->hasAny(['search', 'platform', 'paid_only', 'sort']))
                    <a href="{{ route('admin.fortune.users.index') }}"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm">
                        ล้าง
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ใช้</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Platform</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ดูดวง</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จ่ายเงิน</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดจ่ายรวม</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ดูล่าสุด</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- ผู้ใช้ --}}
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.fortune.users.show', ['platform' => $user->platform ?? 'facebook', 'userId' => $user->facebook_user_id]) }}"
                                   class="hover:underline">
                                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                        {{ $user->facebook_user_name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $user->facebook_user_id }}
                                    </div>
                                </a>
                            </td>

                            {{-- Platform --}}
                            <td class="px-4 py-3 text-center">
                                @if(($user->platform ?? 'facebook') === 'line')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">🟢 LINE</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">📘 Facebook</span>
                                @endif
                            </td>

                            {{-- จำนวนดูดวง --}}
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $user->total_readings }}</div>
                                @if($user->deep_readings > 0)
                                    <div class="text-xs text-purple-600 dark:text-purple-400">{{ $user->deep_readings }} ละเอียด</div>
                                @endif
                            </td>

                            {{-- จ่ายเงิน --}}
                            <td class="px-4 py-3 text-center">
                                @if($user->paid_readings > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        {{ $user->paid_readings }} ครั้ง
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">ฟรี</span>
                                @endif
                            </td>

                            {{-- ยอดจ่ายรวม --}}
                            <td class="px-4 py-3 text-right">
                                @if($user->total_spent > 0)
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">฿{{ number_format($user->total_spent, 0) }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>

                            {{-- ดูล่าสุด --}}
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($user->last_reading_at)->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($user->last_reading_at)->format('d/m/Y H:i') }}
                                </div>
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    {{-- ส่งข้อความ --}}
                                    <button @click="openSendTo('{{ $user->platform ?? 'facebook' }}', '{{ $user->facebook_user_id }}', '{{ addslashes($user->facebook_user_name ?? '') }}')"
                                            class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs transition"
                                            title="ส่งข้อความ">
                                        💬
                                    </button>

                                    {{-- เพิ่มเครดิต --}}
                                    <form action="{{ route('admin.fortune.users.quick-add-credits') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="facebook_user_id" value="{{ $user->facebook_user_id }}">
                                        <input type="hidden" name="platform" value="{{ $user->platform ?? 'facebook' }}">
                                        <input type="hidden" name="facebook_user_name" value="{{ $user->facebook_user_name }}">
                                        <input type="hidden" name="amount" value="3">
                                        <button type="submit" title="เพิ่ม 3 เครดิตฟรี"
                                                class="px-2 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-800/50 text-green-700 dark:text-green-300 rounded-lg text-xs transition">
                                            🎁+3
                                        </button>
                                    </form>

                                    {{-- ดูประวัติ --}}
                                    <a href="{{ route('admin.fortune.users.show', ['platform' => $user->platform ?? 'facebook', 'userId' => $user->facebook_user_id]) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition"
                                       title="ดูประวัติ">
                                        📋
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">🔮</div>
                                <p class="text-lg font-medium">ยังไม่มีผู้ใช้ดูดวง</p>
                                <p class="text-sm mt-1">เมื่อมีคนเริ่มดูดวง ข้อมูลจะแสดงที่นี่</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
        <div class="mt-6">
            {{ $users->links() }}
        </div>
    @endif
</div>

<script>
function fortuneUsers() {
    return {
        showSendMessage: false,
        showBroadcast: false,
        sendPlatform: 'facebook',
        sendUserId: '',

        openSendTo(platform, userId, name) {
            this.sendPlatform = platform;
            this.sendUserId = userId;
            this.showSendMessage = true;
            this.showBroadcast = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>
@endsection
