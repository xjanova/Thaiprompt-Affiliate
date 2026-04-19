@extends('layouts.admin-v3')

@section('title', 'ย้ายทีม MLM โดยตรง')

@section('content')
<div class="container-fluid px-4 py-6" x-data="directTransferApp()">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <svg class="w-8 h-8 inline-block mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                ย้ายทีม MLM โดยตรง
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ย้ายสมาชิกไปยังทีมใหม่ได้โดยตรง รองรับทั้ง Unilevel และ Binary
            </p>
        </div>

        <a href="{{ route('admin.team-transfer.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            กลับไปรายการคำขอ
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 rounded-r-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-r-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-r-lg" role="alert">
            <p class="font-bold mb-2">กรุณาตรวจสอบข้อมูล:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <p class="font-semibold mb-1">คำแนะนำการใช้งาน:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Unilevel:</strong> ย้าย Sponsor โดยตรง - จะอัพเดท path ของลูกทีมทั้งหมดอัตโนมัติ</li>
                    <li><strong>Binary:</strong> ย้ายตำแหน่งใน Binary Tree - ต้องเลือกตำแหน่ง (ซ้าย/ขวา) ที่ว่าง</li>
                    <li>สามารถย้ายทั้ง 2 แผนพร้อมกัน หรือเลือกย้ายเฉพาะแผนที่ต้องการ</li>
                    <li>การย้ายจะส่งการแจ้งเตือนไปยังสมาชิกอัตโนมัติ</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Main Form --}}
    <form action="{{ route('admin.team-transfer.direct.process') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Step 1: เลือกสมาชิกที่จะย้าย --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4">
                <h2 class="text-lg font-bold text-white flex items-center">
                    <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">1</span>
                    เลือกสมาชิกที่ต้องการย้าย
                </h2>
            </div>

            <div class="p-6">
                <div class="relative">
                    <label for="member_search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหาสมาชิก (ชื่อ, อีเมล, หรือรหัสสมาชิก)
                    </label>
                    <input type="text"
                           id="member_search"
                           x-model="memberSearch"
                           @input.debounce.300ms="searchMembers()"
                           @focus="showMemberDropdown = true"
                           @click.away="showMemberDropdown = false"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white"
                           placeholder="พิมพ์เพื่อค้นหา..."
                           autocomplete="off">

                    {{-- Search Results Dropdown --}}
                    <div x-show="showMemberDropdown && memberResults.length > 0"
                         x-transition
                         class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="result in memberResults" :key="result.id">
                            <button type="button"
                                    @click="selectMember(result)"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                         x-text="result.user_name ? result.user_name.charAt(0).toUpperCase() : '?'"></div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="result.user_name"></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="result.member_code"></p>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                    {{-- No Results --}}
                    <div x-show="showMemberDropdown && memberSearch.length >= 2 && memberResults.length === 0 && !isSearching"
                         class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 text-center text-gray-500 dark:text-gray-400">
                        ไม่พบสมาชิกที่ค้นหา
                    </div>
                </div>

                <input type="hidden" name="member_id" x-model="selectedMember.id">

                {{-- Selected Member Display --}}
                <div x-show="selectedMember.id" class="mt-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xl font-bold mr-4"
                                 x-text="selectedMember.user_name ? selectedMember.user_name.charAt(0).toUpperCase() : ''"></div>
                            <div>
                                <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="selectedMember.user_name"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    รหัส: <span class="font-mono" x-text="selectedMember.member_code"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                @click="clearMember()"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Current Position Info --}}
                    <div x-show="selectedMember.id" class="mt-4 grid md:grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Unilevel Sponsor ปัจจุบัน</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedMember.current_unilevel_sponsor || 'ไม่มี (Root)'"></p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Binary Parent ปัจจุบัน</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedMember.current_binary_parent || 'ไม่มี (Root)'"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="selectedMember.current_binary_position">
                                ตำแหน่ง: <span x-text="selectedMember.current_binary_position === 'left' ? 'ซ้าย' : 'ขวา'"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Unilevel Transfer --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
             :class="{ 'opacity-50 pointer-events-none': !selectedMember.id }">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">2</span>
                        ย้าย Unilevel (Sponsor)
                    </h2>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="transfer_unilevel"
                               value="1"
                               x-model="transferUnilevel"
                               class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/30 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/60"></div>
                        <span class="ml-2 text-white font-medium">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>

            <div class="p-6" x-show="transferUnilevel" x-transition>
                <div class="relative">
                    <label for="unilevel_search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหา Sponsor ใหม่
                    </label>
                    <input type="text"
                           id="unilevel_search"
                           x-model="unilevelSearch"
                           @input.debounce.300ms="searchUnilevelSponsor()"
                           @focus="showUnilevelDropdown = true"
                           @click.away="showUnilevelDropdown = false"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:text-white"
                           placeholder="พิมพ์เพื่อค้นหา Sponsor ใหม่..."
                           autocomplete="off"
                           :disabled="!transferUnilevel">

                    {{-- Search Results Dropdown --}}
                    <div x-show="showUnilevelDropdown && unilevelResults.length > 0"
                         x-transition
                         class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="result in unilevelResults" :key="result.id">
                            <button type="button"
                                    @click="selectUnilevelSponsor(result)"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                         x-text="result.user_name ? result.user_name.charAt(0).toUpperCase() : '?'"></div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="result.user_name"></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="result.member_code"></p>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <input type="hidden" name="new_unilevel_sponsor_id" x-model="selectedUnilevelSponsor.id">

                {{-- Selected Sponsor Display --}}
                <div x-show="selectedUnilevelSponsor.id" class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                 x-text="selectedUnilevelSponsor.user_name ? selectedUnilevelSponsor.user_name.charAt(0).toUpperCase() : ''"></div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="selectedUnilevelSponsor.user_name"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedUnilevelSponsor.member_code"></p>
                            </div>
                        </div>
                        <button type="button"
                                @click="clearUnilevelSponsor()"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6 text-center text-gray-500 dark:text-gray-400" x-show="!transferUnilevel">
                <p>เปิดสวิตช์เพื่อย้าย Sponsor ใน Unilevel</p>
            </div>
        </div>

        {{-- Step 3: Binary Transfer --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
             :class="{ 'opacity-50 pointer-events-none': !selectedMember.id }">
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">3</span>
                        ย้าย Binary (ตำแหน่งซ้าย/ขวา)
                    </h2>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="transfer_binary"
                               value="1"
                               x-model="transferBinary"
                               class="sr-only peer">
                        <div class="relative w-11 h-6 bg-white/30 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/60"></div>
                        <span class="ml-2 text-white font-medium">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>

            <div class="p-6" x-show="transferBinary" x-transition>
                <div class="space-y-4">
                    {{-- Binary Parent Search --}}
                    <div class="relative">
                        <label for="binary_search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ค้นหา Binary Parent ใหม่
                        </label>
                        <input type="text"
                               id="binary_search"
                               x-model="binarySearch"
                               @input.debounce.300ms="searchBinaryParent()"
                               @focus="showBinaryDropdown = true"
                               @click.away="showBinaryDropdown = false"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:text-white"
                               placeholder="พิมพ์เพื่อค้นหา Binary Parent ใหม่..."
                               autocomplete="off"
                               :disabled="!transferBinary">

                        {{-- Search Results Dropdown --}}
                        <div x-show="showBinaryDropdown && binaryResults.length > 0"
                             x-transition
                             class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="result in binaryResults" :key="result.id">
                                <button type="button"
                                        @click="selectBinaryParent(result)"
                                        class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                             x-text="result.user_name ? result.user_name.charAt(0).toUpperCase() : '?'"></div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white" x-text="result.user_name"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="result.member_code"></p>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <input type="hidden" name="new_binary_parent_id" x-model="selectedBinaryParent.id">

                    {{-- Selected Binary Parent Display --}}
                    <div x-show="selectedBinaryParent.id" class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                     x-text="selectedBinaryParent.user_name ? selectedBinaryParent.user_name.charAt(0).toUpperCase() : ''"></div>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="selectedBinaryParent.user_name"></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedBinaryParent.member_code"></p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="clearBinaryParent()"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Binary Position Selection --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Left Position --}}
                            <label class="relative cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': !binaryPositions.left.available }">
                                <input type="radio"
                                       name="new_binary_position"
                                       value="left"
                                       x-model="selectedBinaryPosition"
                                       class="sr-only peer"
                                       :disabled="!binaryPositions.left.available">
                                <div class="p-4 border-2 rounded-xl transition-all"
                                     :class="selectedBinaryPosition === 'left' ? 'border-purple-500 bg-purple-100 dark:bg-purple-900/40' : 'border-gray-300 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-700'">
                                    <div class="flex items-center justify-center mb-2">
                                        <svg class="w-8 h-8" :class="binaryPositions.left.available ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                                        </svg>
                                    </div>
                                    <p class="text-center font-semibold text-gray-900 dark:text-white">ตำแหน่งซ้าย</p>
                                    <p class="text-center text-xs mt-1"
                                       :class="binaryPositions.left.available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span x-show="binaryPositions.left.available">ว่าง</span>
                                        <span x-show="!binaryPositions.left.available">ไม่ว่าง - <span x-text="binaryPositions.left.occupied_by?.user_name"></span></span>
                                    </p>
                                </div>
                            </label>

                            {{-- Right Position --}}
                            <label class="relative cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': !binaryPositions.right.available }">
                                <input type="radio"
                                       name="new_binary_position"
                                       value="right"
                                       x-model="selectedBinaryPosition"
                                       class="sr-only peer"
                                       :disabled="!binaryPositions.right.available">
                                <div class="p-4 border-2 rounded-xl transition-all"
                                     :class="selectedBinaryPosition === 'right' ? 'border-purple-500 bg-purple-100 dark:bg-purple-900/40' : 'border-gray-300 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-700'">
                                    <div class="flex items-center justify-center mb-2">
                                        <svg class="w-8 h-8" :class="binaryPositions.right.available ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                    <p class="text-center font-semibold text-gray-900 dark:text-white">ตำแหน่งขวา</p>
                                    <p class="text-center text-xs mt-1"
                                       :class="binaryPositions.right.available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span x-show="binaryPositions.right.available">ว่าง</span>
                                        <span x-show="!binaryPositions.right.available">ไม่ว่าง - <span x-text="binaryPositions.right.occupied_by?.user_name"></span></span>
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 text-center text-gray-500 dark:text-gray-400" x-show="!transferBinary">
                <p>เปิดสวิตช์เพื่อย้ายตำแหน่งใน Binary Tree</p>
            </div>
        </div>

        {{-- Step 4: หมายเหตุ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
             :class="{ 'opacity-50 pointer-events-none': !selectedMember.id }">
            <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-4">
                <h2 class="text-lg font-bold text-white flex items-center">
                    <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">4</span>
                    หมายเหตุ (ไม่บังคับ)
                </h2>
            </div>

            <div class="p-6">
                <textarea name="admin_notes"
                          rows="3"
                          class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gray-500 dark:text-white"
                          placeholder="เหตุผลในการย้าย หรือหมายเหตุอื่นๆ..."
                          maxlength="1000">{{ old('admin_notes') }}</textarea>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.team-transfer.index') }}"
               class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                    :disabled="!canSubmit">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                ดำเนินการย้ายทีม
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function directTransferApp() {
    return {
        // Member selection
        memberSearch: '',
        memberResults: [],
        showMemberDropdown: false,
        isSearching: false,
        selectedMember: {
            id: {{ $selectedMember->id ?? 'null' }},
            member_code: '{{ $selectedMember->member_code ?? '' }}',
            user_name: '{{ $selectedMember->user->name ?? '' }}',
            current_unilevel_sponsor: '{{ $selectedMember?->unilevelSponsor?->user?->name ?? '' }}',
            current_binary_parent: '{{ $selectedMember?->binaryParent?->user?->name ?? '' }}',
            current_binary_position: '{{ $selectedMember?->binary_position ?? '' }}'
        },

        // Unilevel transfer
        transferUnilevel: false,
        unilevelSearch: '',
        unilevelResults: [],
        showUnilevelDropdown: false,
        selectedUnilevelSponsor: { id: null, member_code: '', user_name: '' },

        // Binary transfer
        transferBinary: false,
        binarySearch: '',
        binaryResults: [],
        showBinaryDropdown: false,
        selectedBinaryParent: { id: null, member_code: '', user_name: '' },
        selectedBinaryPosition: '',
        binaryPositions: {
            left: { available: true, occupied_by: null },
            right: { available: true, occupied_by: null }
        },

        get canSubmit() {
            if (!this.selectedMember.id) return false;
            if (!this.transferUnilevel && !this.transferBinary) return false;
            if (this.transferUnilevel && !this.selectedUnilevelSponsor.id) return false;
            if (this.transferBinary && (!this.selectedBinaryParent.id || !this.selectedBinaryPosition)) return false;
            return true;
        },

        async searchMembers() {
            if (this.memberSearch.length < 2) {
                this.memberResults = [];
                return;
            }

            this.isSearching = true;
            try {
                const response = await fetch(`{{ route('admin.team-transfer.direct.search-members') }}?q=${encodeURIComponent(this.memberSearch)}`);
                this.memberResults = await response.json();
            } catch (e) {
                console.error('Search error:', e);
                this.memberResults = [];
            }
            this.isSearching = false;
        },

        selectMember(result) {
            this.selectedMember = {
                id: result.id,
                member_code: result.member_code,
                user_name: result.user_name,
                current_unilevel_sponsor: '',
                current_binary_parent: '',
                current_binary_position: ''
            };
            this.memberSearch = result.display;
            this.showMemberDropdown = false;

            // Reset selections
            this.clearUnilevelSponsor();
            this.clearBinaryParent();

            // Load current member data
            this.loadMemberDetails(result.id);
        },

        async loadMemberDetails(memberId) {
            // We would need an API endpoint to get full member details
            // For now, the user can see after the form reloads
        },

        clearMember() {
            this.selectedMember = { id: null, member_code: '', user_name: '', current_unilevel_sponsor: '', current_binary_parent: '', current_binary_position: '' };
            this.memberSearch = '';
            this.transferUnilevel = false;
            this.transferBinary = false;
            this.clearUnilevelSponsor();
            this.clearBinaryParent();
        },

        async searchUnilevelSponsor() {
            if (this.unilevelSearch.length < 2) {
                this.unilevelResults = [];
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.team-transfer.direct.search-members') }}?q=${encodeURIComponent(this.unilevelSearch)}&exclude_id=${this.selectedMember.id}`);
                this.unilevelResults = await response.json();
            } catch (e) {
                console.error('Search error:', e);
                this.unilevelResults = [];
            }
        },

        selectUnilevelSponsor(result) {
            this.selectedUnilevelSponsor = {
                id: result.id,
                member_code: result.member_code,
                user_name: result.user_name
            };
            this.unilevelSearch = result.display;
            this.showUnilevelDropdown = false;
        },

        clearUnilevelSponsor() {
            this.selectedUnilevelSponsor = { id: null, member_code: '', user_name: '' };
            this.unilevelSearch = '';
        },

        async searchBinaryParent() {
            if (this.binarySearch.length < 2) {
                this.binaryResults = [];
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.team-transfer.direct.search-members') }}?q=${encodeURIComponent(this.binarySearch)}&exclude_id=${this.selectedMember.id}`);
                this.binaryResults = await response.json();
            } catch (e) {
                console.error('Search error:', e);
                this.binaryResults = [];
            }
        },

        selectBinaryParent(result) {
            this.selectedBinaryParent = {
                id: result.id,
                member_code: result.member_code,
                user_name: result.user_name
            };
            this.binarySearch = result.display;
            this.showBinaryDropdown = false;
            this.selectedBinaryPosition = '';

            // Load available positions
            this.loadBinaryPositions(result.id);
        },

        async loadBinaryPositions(parentId) {
            try {
                const response = await fetch(`{{ url('admin/team-transfer/direct/binary-positions') }}/${parentId}`);
                const data = await response.json();
                this.binaryPositions = data;

                // Auto-select if only one position available
                if (data.left.available && !data.right.available) {
                    this.selectedBinaryPosition = 'left';
                } else if (data.right.available && !data.left.available) {
                    this.selectedBinaryPosition = 'right';
                }
            } catch (e) {
                console.error('Load positions error:', e);
            }
        },

        clearBinaryParent() {
            this.selectedBinaryParent = { id: null, member_code: '', user_name: '' };
            this.binarySearch = '';
            this.selectedBinaryPosition = '';
            this.binaryPositions = {
                left: { available: true, occupied_by: null },
                right: { available: true, occupied_by: null }
            };
        }
    }
}
</script>
@endpush
@endsection
