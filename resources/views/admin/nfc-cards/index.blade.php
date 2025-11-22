@extends('layouts.admin-v3')

@section('title', 'จัดการบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{
    showFilters: false,
    selectedCards: [],
    selectAll: false,
    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedCards = Array.from(document.querySelectorAll('input[name=\'card_ids[]\']')).map(el => el.value);
        } else {
            this.selectedCards = [];
        }
    }
}">
    {{-- Header Section พร้อมปุ่มสร้างบัตรใหม่ --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-credit-card text-blue-600 dark:text-blue-400"></i>
                จัดการบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                ระบบจัดการและออกบัตร NFC สำหรับผู้ใช้งาน
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.nfc-cards.create') }}"
               class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                ออกบัตรใหม่
            </a>
            <button @click="showFilters = !showFilters"
                    class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-filter"></i>
                <span x-show="!showFilters">แสดงตัวกรอง</span>
                <span x-show="showFilters">ซ่อนตัวกรอง</span>
            </button>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition
             x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Statistics Cards สถิติภาพรวม --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        {{-- Total Cards --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">บัตรทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['total_cards']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-credit-card text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Cards --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">บัตรใช้งานอยู่</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['active_cards']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Paired Cards --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">บัตรที่จับคู่แล้ว</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['paired_cards']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Blocked Cards --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">บัตรที่ถูกบล็อก</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['blocked_cards']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-ban text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Balance --}}
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm font-medium">ยอดเงินรวม</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['total_balance'], 2) }}</h3>
                    <p class="text-amber-100 text-xs mt-1">บาท</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section ส่วนตัวกรอง --}}
    <div x-show="showFilters"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.nfc-cards.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-1"></i>
                        ค้นหา
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="เลขบัตร, ชื่อบัตร, ชื่อผู้ใช้..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>บล็อก</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>พักการใช้งาน</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                    </select>
                </div>

                {{-- Card Type Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-layer-group mr-1"></i>
                        ประเภทบัตร
                    </label>
                    <select name="card_type"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="standard" {{ request('card_type') === 'standard' ? 'selected' : '' }}>มาตรฐาน</option>
                        <option value="premium" {{ request('card_type') === 'premium' ? 'selected' : '' }}>พรีเมียม</option>
                        <option value="vip" {{ request('card_type') === 'vip' ? 'selected' : '' }}>วีไอพี</option>
                    </select>
                </div>

                {{-- Paired Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-link mr-1"></i>
                        สถานะการจับคู่
                    </label>
                    <select name="is_paired"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ request('is_paired') === '1' ? 'selected' : '' }}>จับคู่แล้ว</option>
                        <option value="0" {{ request('is_paired') === '0' ? 'selected' : '' }}>ยังไม่ได้จับคู่</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i>
                    กรองข้อมูล
                </button>
                <a href="{{ route('admin.nfc-cards.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    รีเซ็ต
                </a>
                <a href="{{ route('admin.nfc-cards.export', request()->all()) }}"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2 ml-auto">
                    <i class="fas fa-file-export"></i>
                    ส่งออก CSV
                </a>
            </div>
        </form>
    </div>

    {{-- Cards Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox"
                                   x-model="selectAll"
                                   @change="toggleSelectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            เลขบัตร
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ชื่อบัตร
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ยอดเงิน
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            การใช้งานล่าสุด
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($cards as $card)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Checkbox --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox"
                                       name="card_ids[]"
                                       value="{{ $card->id }}"
                                       x-model="selectedCards"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>

                            {{-- Card Number --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-credit-card text-blue-600 dark:text-blue-400"></i>
                                    <span class="text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ $card->card_number }}
                                    </span>
                                </div>
                            </td>

                            {{-- Card Name --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $card->card_name ?? '-' }}
                                </div>
                            </td>

                            {{-- Card Type --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($card->card_type === 'standard')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-star mr-1"></i>
                                        มาตรฐาน
                                    </span>
                                @elseif($card->card_type === 'premium')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                        <i class="fas fa-gem mr-1"></i>
                                        พรีเมียม
                                    </span>
                                @elseif($card->card_type === 'vip')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        <i class="fas fa-crown mr-1"></i>
                                        วีไอพี
                                    </span>
                                @endif
                            </td>

                            {{-- User --}}
                            <td class="px-6 py-4">
                                @if($card->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                            {{ strtoupper(substr($card->user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $card->user->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $card->user->email }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400 italic">ยังไม่ได้จับคู่</span>
                                @endif
                            </td>

                            {{-- Balance --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    ฿{{ number_format($card->balance, 2) }}
                                </div>
                                @if($card->credit_limit > 0)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        วงเงิน: ฿{{ number_format($card->credit_limit, 2) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($card->status === 'active')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        ใช้งานอยู่
                                    </span>
                                @elseif($card->status === 'inactive')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        ไม่ใช้งาน
                                    </span>
                                @elseif($card->status === 'blocked')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                        <i class="fas fa-ban mr-1"></i>
                                        บล็อก
                                    </span>
                                @elseif($card->status === 'suspended')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                        <i class="fas fa-pause-circle mr-1"></i>
                                        พักการใช้งาน
                                    </span>
                                @elseif($card->status === 'expired')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        <i class="fas fa-clock mr-1"></i>
                                        หมดอายุ
                                    </span>
                                @endif

                                @if($card->is_paired)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            <i class="fas fa-link mr-1 text-[10px]"></i>
                                            จับคู่แล้ว
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Last Used --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($card->last_used_at)
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs">{{ $card->last_used_at->diffForHumans() }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $card->last_used_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs italic">ยังไม่เคยใช้งาน</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2" x-data="{ open: false }">
                                    {{-- View --}}
                                    <a href="{{ route('admin.nfc-cards.show', $card) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.nfc-cards.edit', $card) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                                       title="แก้ไข">
                                        <i class="fas fa-edit text-lg"></i>
                                    </a>

                                    {{-- Top Up --}}
                                    <a href="{{ route('admin.nfc-cards.topup.form', $card) }}"
                                       class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors"
                                       title="เติมเงิน">
                                        <i class="fas fa-money-bill-wave text-lg"></i>
                                    </a>

                                    {{-- More Actions Dropdown --}}
                                    <div class="relative">
                                        <button @click="open = !open"
                                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                                                title="เพิ่มเติม">
                                            <i class="fas fa-ellipsis-v text-lg"></i>
                                        </button>

                                        {{-- Dropdown Menu --}}
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                                            <div class="py-1">
                                                @if(!$card->is_paired)
                                                    <a href="{{ route('admin.nfc-cards.pair.form', $card) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <i class="fas fa-link mr-2"></i>
                                                        จับคู่กับผู้ใช้
                                                    </a>
                                                @else
                                                    <form action="{{ route('admin.nfc-cards.unpair', $card) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                                onclick="return confirm('ยืนยันการยกเลิกการจับคู่?')">
                                                            <i class="fas fa-unlink mr-2"></i>
                                                            ยกเลิกการจับคู่
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($card->status === 'active')
                                                    <form action="{{ route('admin.nfc-cards.deactivate', $card) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-stop-circle mr-2"></i>
                                                            ปิดการใช้งาน
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.nfc-cards.activate', $card) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-play-circle mr-2"></i>
                                                            เปิดการใช้งาน
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($card->status !== 'blocked')
                                                    <a href="#"
                                                       @click.prevent="open = false; $dispatch('open-block-modal', { card: {{ $card->id }} })"
                                                       class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <i class="fas fa-ban mr-2"></i>
                                                        บล็อกบัตร
                                                    </a>
                                                @else
                                                    <form action="{{ route('admin.nfc-cards.unblock', $card) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-check-circle mr-2"></i>
                                                            ปลดบล็อก
                                                        </button>
                                                    </form>
                                                @endif

                                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                                                <form action="{{ route('admin.nfc-cards.destroy', $card) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('คุณแน่ใจที่จะลบบัตรนี้? การลบจะไม่สามารถกู้คืนได้')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <i class="fas fa-trash mr-2"></i>
                                                        ลบบัตร
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-credit-card text-6xl mb-4 text-gray-300 dark:text-gray-600"></i>
                                    <p class="text-lg font-medium mb-2">ไม่พบข้อมูลบัตร NFC</p>
                                    <p class="text-sm mb-4">ยังไม่มีบัตรในระบบหรือไม่พบผลลัพธ์ที่ค้นหา</p>
                                    <a href="{{ route('admin.nfc-cards.create') }}"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                        <i class="fas fa-plus mr-1"></i>
                                        ออกบัตรใหม่เลย
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($cards->hasPages())
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $cards->links() }}
            </div>
        @endif
    </div>

    {{-- Selected Cards Action Bar ปุ่มการจัดการหลายรายการ --}}
    <div x-show="selectedCards.length > 0"
         x-transition
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center gap-4 z-40">
        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
            เลือกแล้ว <span class="font-bold text-blue-600 dark:text-blue-400" x-text="selectedCards.length"></span> รายการ
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-file-export mr-1"></i>
                ส่งออก
            </button>
            <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-trash mr-1"></i>
                ลบ
            </button>
            <button @click="selectedCards = []; selectAll = false"
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-times mr-1"></i>
                ยกเลิก
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom scrollbar for dark mode */
    .dark ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .dark ::-webkit-scrollbar-track {
        background: #1f2937;
    }

    .dark ::-webkit-scrollbar-thumb {
        background: #4b5563;
        border-radius: 4px;
    }

    .dark ::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }
</style>
@endpush
