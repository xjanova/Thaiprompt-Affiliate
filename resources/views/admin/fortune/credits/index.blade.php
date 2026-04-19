@extends('layouts.admin-v3')

@section('title', 'จัดการเครดิตดูดวงฟรีรายคน')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="creditManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                🎁 จัดการเครดิตดูดวงฟรีรายคน
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                เพิ่มเครดิต / รีเซ็ตสิทธิ์ฟรี / ให้ดูฟรีไม่จำกัดเป็นรายคน
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <button @click="showAddForm = true"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">+</span>
                เพิ่มเครดิตผู้ใช้
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

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">ดูฟรีไม่จำกัด</span>
                <span class="text-2xl">🌟</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['unlimited_users']) }}</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">มีเครดิตเหลือ</span>
                <span class="text-2xl">🎫</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['users_with_credits']) }}</div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-orange-100 text-sm">เครดิตแจกทั้งหมด</span>
                <span class="text-2xl">🎁</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_credits_given']) }}</div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-red-100 text-sm">เครดิตใช้ไปแล้ว</span>
                <span class="text-2xl">📊</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_credits_used']) }}</div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Add Credits Form (Modal-like) --}}
    <div x-show="showAddForm" x-cloak
         class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-2 border-blue-200 dark:border-blue-800">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            🎁 เพิ่มเครดิตให้ผู้ใช้
        </h2>
        <form action="{{ route('admin.fortune.credits.add-credits') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Facebook User ID *
                    </label>
                    <input type="text" name="facebook_user_id" required
                           placeholder="เช่น 1234567890"
                           class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อผู้ใช้ (ถ้ามี)
                    </label>
                    <input type="text" name="facebook_user_name"
                           placeholder="ชื่อ Facebook"
                           class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        จำนวนเครดิต *
                    </label>
                    <input type="number" name="amount" required min="1" max="999" value="5"
                           class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        หมายเหตุ
                    </label>
                    <input type="text" name="note"
                           placeholder="เหตุผลที่เพิ่ม"
                           class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    เพิ่มเครดิต
                </button>
                <button type="button" @click="showAddForm = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>

    {{-- Search & Filter --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <form method="GET" action="{{ route('admin.fortune.credits.index') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหา Facebook User ID หรือชื่อ..."
                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="has_credits" value="1" {{ request('has_credits') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    มีเครดิตเหลือ
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="unlimited" value="1" {{ request('unlimited') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500">
                    ไม่จำกัด
                </label>
                <button type="submit"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    ค้นหา
                </button>
                @if(request()->hasAny(['search', 'has_credits', 'unlimited']))
                    <a href="{{ route('admin.fortune.credits.index') }}"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                        ล้าง
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Credits Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ผู้ใช้
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            เครดิต
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            สถานะพิเศษ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            หมายเหตุ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            อัปเดตล่าสุด
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($credits as $credit)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- ผู้ใช้ --}}
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $credit->facebook_user_name ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $credit->facebook_user_id }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $credit->platform }}
                                </div>
                            </td>

                            {{-- เครดิต --}}
                            <td class="px-4 py-3 text-center">
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ $credit->getRemainingCredits() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ใช้ {{ $credit->credits_used }} / {{ $credit->bonus_credits }}
                                </div>
                            </td>

                            {{-- สถานะพิเศษ --}}
                            <td class="px-4 py-3 text-center">
                                @if($credit->isCurrentlyUnlimited())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                                        🌟 ไม่จำกัด
                                    </span>
                                    @if($credit->unlimited_until)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            ถึง {{ $credit->unlimited_until->format('d/m/Y') }}
                                        </div>
                                    @endif
                                @elseif($credit->isDailyResetActive())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        🔄 รีเซ็ตวันนี้
                                    </span>
                                @elseif($credit->getRemainingCredits() > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                        🎫 มีเครดิต
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- หมายเหตุ --}}
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400 max-w-[200px] truncate">
                                    {{ $credit->note ?? '-' }}
                                </div>
                            </td>

                            {{-- อัปเดตล่าสุด --}}
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $credit->updated_at?->diffForHumans() ?? '-' }}
                                </div>
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1" x-data="{ openMenu: false }">
                                    <button @click="openMenu = !openMenu"
                                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition">
                                        ...
                                    </button>
                                    <div x-show="openMenu" @click.away="openMenu = false" x-cloak
                                         class="absolute right-4 mt-8 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                                        {{-- รีเซ็ตสิทธิ์ฟรีวันนี้ --}}
                                        <form action="{{ route('admin.fortune.credits.reset-daily', $credit) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 text-sm text-green-700 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                🔄 รีเซ็ตสิทธิ์ฟรีวันนี้
                                            </button>
                                        </form>

                                        {{-- ตั้งค่าไม่จำกัด --}}
                                        @if($credit->is_unlimited)
                                            <form action="{{ route('admin.fortune.credits.set-unlimited', $credit) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="is_unlimited" value="0">
                                                <button type="submit"
                                                        class="w-full text-left px-4 py-2 text-sm text-orange-700 dark:text-orange-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                    ⏹ ปิดดูฟรีไม่จำกัด
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.fortune.credits.set-unlimited', $credit) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="is_unlimited" value="1">
                                                <button type="submit"
                                                        class="w-full text-left px-4 py-2 text-sm text-purple-700 dark:text-purple-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                    🌟 เปิดดูฟรีไม่จำกัด
                                                </button>
                                            </form>
                                        @endif

                                        <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                        {{-- รีเซ็ตทั้งหมด --}}
                                        <form action="{{ route('admin.fortune.credits.reset-all', $credit) }}" method="POST"
                                              onsubmit="return confirm('ยืนยันรีเซ็ตเครดิตทั้งหมด?')">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 text-sm text-yellow-700 dark:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                🔃 รีเซ็ตเครดิตทั้งหมด
                                            </button>
                                        </form>

                                        {{-- ลบ --}}
                                        <form action="{{ route('admin.fortune.credits.destroy', $credit) }}" method="POST"
                                              onsubmit="return confirm('ยืนยันลบข้อมูลเครดิตของผู้ใช้นี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                🗑 ลบ
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">🎁</div>
                                <p class="text-lg font-medium">ยังไม่มีข้อมูลเครดิตพิเศษ</p>
                                <p class="text-sm mt-1">คลิก "เพิ่มเครดิตผู้ใช้" เพื่อเริ่มต้น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($credits->hasPages())
        <div class="mt-6">
            {{ $credits->links() }}
        </div>
    @endif
</div>

<script>
function creditManager() {
    return {
        showAddForm: false,
    }
}
</script>
@endsection
