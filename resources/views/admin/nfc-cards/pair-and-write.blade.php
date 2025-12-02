@extends('layouts.admin-v3')

@section('title', 'จับคู่และเขียนบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="nfcPairAndWrite()">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-wifi text-purple-600 dark:text-purple-400"></i>
                จับคู่และเขียนบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                เขียนข้อมูลลงบัตร NFC และจับคู่กับผู้ใช้
            </p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-xl"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Web NFC Support Check --}}
    <template x-if="!nfcSupported">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-400 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-xl mt-0.5"></i>
                <div>
                    <h3 class="font-semibold">เบราว์เซอร์ไม่รองรับ Web NFC</h3>
                    <p class="text-sm mt-1">กรุณาใช้ Chrome บน Android เวอร์ชัน 89 ขึ้นไป หรือ Edge บน Windows</p>
                    <p class="text-sm mt-1">หรือตรวจสอบว่าเปิดใช้งาน HTTPS แล้ว</p>
                </div>
            </div>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Step 1: อ่านบัตร NFC --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                        อ่านบัตร NFC
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">แตะบัตร NFC กับเครื่องอ่านเพื่ออ่านข้อมูล</p>
                </div>

                <div class="p-6">
                    {{-- NFC Reading Status --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-center p-8 rounded-xl transition-all duration-300"
                             :class="{
                                 'bg-gray-100 dark:bg-gray-700': readState === 'idle',
                                 'bg-blue-100 dark:bg-blue-900/30 animate-pulse': readState === 'reading',
                                 'bg-green-100 dark:bg-green-900/30': readState === 'success',
                                 'bg-red-100 dark:bg-red-900/30': readState === 'error'
                             }">
                            <div class="text-center">
                                {{-- Icon --}}
                                <div class="mb-4">
                                    <template x-if="readState === 'idle'">
                                        <i class="fas fa-wifi text-6xl text-gray-400 dark:text-gray-500"></i>
                                    </template>
                                    <template x-if="readState === 'reading'">
                                        <i class="fas fa-spinner fa-spin text-6xl text-blue-600 dark:text-blue-400"></i>
                                    </template>
                                    <template x-if="readState === 'success'">
                                        <i class="fas fa-check-circle text-6xl text-green-600 dark:text-green-400"></i>
                                    </template>
                                    <template x-if="readState === 'error'">
                                        <i class="fas fa-times-circle text-6xl text-red-600 dark:text-red-400"></i>
                                    </template>
                                </div>

                                {{-- Status Text --}}
                                <p class="font-semibold text-lg"
                                   :class="{
                                       'text-gray-600 dark:text-gray-400': readState === 'idle',
                                       'text-blue-600 dark:text-blue-400': readState === 'reading',
                                       'text-green-600 dark:text-green-400': readState === 'success',
                                       'text-red-600 dark:text-red-400': readState === 'error'
                                   }">
                                    <span x-text="readStatusText"></span>
                                </p>

                                {{-- Card UID --}}
                                <template x-if="cardUID">
                                    <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-inner">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">NFC UID ที่อ่านได้</p>
                                        <p class="font-mono font-bold text-lg text-gray-900 dark:text-white" x-text="cardUID"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Read Button --}}
                    <div class="flex gap-4">
                        <button type="button"
                                @click="startReading()"
                                :disabled="readState === 'reading' || !nfcSupported"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-wifi"></i>
                            <span x-text="readState === 'reading' ? 'กำลังอ่าน...' : 'เริ่มอ่านบัตร NFC'"></span>
                        </button>

                        <template x-if="readState === 'reading'">
                            <button type="button"
                                    @click="stopReading()"
                                    class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-stop"></i>
                                หยุด
                            </button>
                        </template>
                    </div>

                    {{-- Card Info (after reading) --}}
                    <template x-if="cardInfo">
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-id-card text-blue-600"></i>
                                ข้อมูลบัตรที่อ่านได้
                            </h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">ประเภทบัตร:</span>
                                    <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="cardInfo.cardType || 'ไม่ทราบ'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">หน่วยความจำ:</span>
                                    <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="cardInfo.memorySize ? cardInfo.memorySize + ' bytes' : 'ไม่ทราบ'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">เขียน NDEF ได้:</span>
                                    <span class="ml-2 font-semibold" :class="cardInfo.isWritable ? 'text-green-600' : 'text-red-600'" x-text="cardInfo.isWritable ? 'ใช่' : 'ไม่'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">จำนวน Records:</span>
                                    <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="cardInfo.recordsCount || 0"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Step 2: เขียนข้อมูลลงบัตร --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                        เขียนข้อมูลลงบัตร NFC
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">เขียนรหัสป้องกันปลอมและข้อมูลบัตรลงบัตร NFC</p>
                </div>

                <div class="p-6">
                    {{-- Write Template Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เลือก Template ที่จะเขียน
                        </label>
                        <select x-model="selectedTemplate"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            <option value="member_card">บัตรสมาชิก (Member Card)</option>
                            <option value="url_shortcut">URL Shortcut</option>
                            <option value="business_card">นามบัตร (Business Card)</option>
                            <option value="access_card">บัตรเข้า-ออก (Access Card)</option>
                        </select>
                    </div>

                    {{-- Write Data Fields --}}
                    <div class="space-y-4 mb-6">
                        {{-- Card Number (auto-filled) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เลขบัตร (Card Number)
                            </label>
                            <input type="text"
                                   value="{{ $nfcCard->card_number }}"
                                   readonly
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-white font-mono">
                        </div>

                        {{-- URL (for URL template) --}}
                        <template x-if="selectedTemplate === 'url_shortcut'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    URL ที่จะเขียน
                                </label>
                                <input type="url"
                                       x-model="writeData.url"
                                       placeholder="https://example.com"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </template>

                        {{-- Custom Text --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความเพิ่มเติม (ถ้ามี)
                            </label>
                            <input type="text"
                                   x-model="writeData.customText"
                                   placeholder="ข้อความที่ต้องการเขียนลงบัตร"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    {{-- Write Status --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-center p-6 rounded-xl transition-all duration-300"
                             :class="{
                                 'bg-gray-100 dark:bg-gray-700': writeState === 'idle',
                                 'bg-purple-100 dark:bg-purple-900/30 animate-pulse': writeState === 'writing',
                                 'bg-green-100 dark:bg-green-900/30': writeState === 'success',
                                 'bg-red-100 dark:bg-red-900/30': writeState === 'error'
                             }">
                            <div class="text-center">
                                <template x-if="writeState === 'idle'">
                                    <p class="text-gray-600 dark:text-gray-400">พร้อมเขียนข้อมูล</p>
                                </template>
                                <template x-if="writeState === 'writing'">
                                    <p class="text-purple-600 dark:text-purple-400 flex items-center gap-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        กำลังเขียน... แตะบัตรค้างไว้
                                    </p>
                                </template>
                                <template x-if="writeState === 'success'">
                                    <p class="text-green-600 dark:text-green-400 flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i>
                                        เขียนข้อมูลสำเร็จ!
                                    </p>
                                </template>
                                <template x-if="writeState === 'error'">
                                    <p class="text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-times-circle"></i>
                                        <span x-text="writeError"></span>
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Write Buttons --}}
                    <div class="flex gap-4">
                        <button type="button"
                                @click="generateAndWrite()"
                                :disabled="writeState === 'writing' || !nfcSupported || !cardUID"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-pen"></i>
                            <span x-text="writeState === 'writing' ? 'กำลังเขียน...' : 'เขียนข้อมูลลงบัตร'"></span>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        ต้องอ่านบัตรก่อนเพื่อให้ได้ NFC UID สำหรับสร้างรหัสป้องกันปลอม
                    </p>
                </div>
            </div>

            {{-- Step 3: จับคู่กับผู้ใช้ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
                        จับคู่บัตรกับผู้ใช้
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">เลือกผู้ใช้ที่ต้องการจับคู่กับบัตรนี้</p>
                </div>

                <div class="p-6">
                    @if($nfcCard->isPaired())
                        {{-- Already Paired --}}
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($nfcCard->user?->name ?? '?', 0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $nfcCard->user?->name ?? 'Unknown' }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $nfcCard->user?->email ?? '' }}</p>
                                </div>
                                <span class="px-3 py-1 bg-green-600 text-white text-xs font-semibold rounded-full">จับคู่แล้ว</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.nfc-cards.unpair', $nfcCard) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('ยืนยันยกเลิกการจับคู่บัตรนี้?')"
                                    class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-unlink"></i>
                                ยกเลิกการจับคู่
                            </button>
                        </form>
                    @else
                        {{-- User Selection --}}
                        <form action="{{ route('admin.nfc-cards.pair', $nfcCard) }}" method="POST">
                            @csrf

                            {{-- Search Box --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-search mr-1"></i>
                                    ค้นหาผู้ใช้
                                </label>
                                <input type="text"
                                       x-model="userSearch"
                                       placeholder="ค้นหาด้วย ชื่อ, อีเมล, หรือรหัสผู้ใช้..."
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            {{-- Selected User Display with Business Card Preview --}}
                            <div x-show="selectedUser" x-transition class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800 mb-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-green-800 dark:text-green-300 flex items-center gap-2">
                                        <i class="fas fa-address-card"></i>
                                        นามบัตรที่จะเขียนลงบัตร NFC
                                    </h4>
                                    <button type="button"
                                            @click="selectedUser = null"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>

                                {{-- Business Card Preview --}}
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-inner">
                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-xl shrink-0">
                                            <span x-text="selectedUser ? selectedUser.name.substring(0, 2).toUpperCase() : ''"></span>
                                        </div>
                                        <div class="flex-1 space-y-1">
                                            <div class="font-bold text-lg text-gray-900 dark:text-white" x-text="selectedUser ? selectedUser.name : ''"></div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2" x-show="selectedUser && selectedUser.member_number">
                                                <i class="fas fa-id-badge text-blue-500"></i>
                                                <span x-text="selectedUser ? selectedUser.member_number : ''"></span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2" x-show="selectedUser && selectedUser.phone">
                                                <i class="fas fa-phone text-green-500"></i>
                                                <span x-text="selectedUser ? selectedUser.phone : ''"></span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                                <i class="fas fa-envelope text-red-500"></i>
                                                <span x-text="selectedUser ? selectedUser.email : ''"></span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2" x-show="selectedUser && selectedUser.line_id">
                                                <i class="fab fa-line text-green-600"></i>
                                                <span x-text="selectedUser ? selectedUser.line_id : ''"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Referral Link Preview --}}
                                    <div class="mt-4 p-3 bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                                        <div class="text-xs text-orange-700 dark:text-orange-300 mb-1 flex items-center gap-1">
                                            <i class="fas fa-link"></i>
                                            Referral Link (เมื่อแตะบัตรจะไปหน้านี้)
                                        </div>
                                        <div class="font-mono text-xs text-orange-800 dark:text-orange-200 break-all" x-text="getReferralUrl()"></div>
                                    </div>
                                </div>

                                <input type="hidden" name="user_id" x-bind:value="selectedUser ? selectedUser.id : ''">
                            </div>

                            {{-- Users List --}}
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden max-h-64 overflow-y-auto mb-4">
                                <template x-for="user in filteredUsers" :key="user.id">
                                    <div @click="selectedUser = user"
                                         :class="selectedUser && selectedUser.id === user.id ? 'bg-green-100 dark:bg-green-900/30 border-l-4 border-green-600' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                                         class="p-3 border-b border-gray-200 dark:border-gray-700 cursor-pointer transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                                <span x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                            </div>
                                            <div class="flex-1">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="user.name"></div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400" x-text="user.email"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="filteredUsers.length === 0"
                                     class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-search text-3xl mb-2 text-gray-300 dark:text-gray-600"></i>
                                    <p class="text-sm">ไม่พบผู้ใช้ที่ตรงกับคำค้นหา</p>
                                </div>
                            </div>

                            <button type="submit"
                                    x-bind:disabled="!selectedUser"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-link"></i>
                                จับคู่บัตรกับผู้ใช้
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Step 4: บันทึกข้อมูล NFC ลงระบบ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-8 h-8 bg-orange-600 text-white rounded-full flex items-center justify-center text-sm font-bold">4</span>
                        บันทึกข้อมูล NFC ลงระบบ
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">บันทึก NFC UID และรหัสป้องกันปลอมลงฐานข้อมูล</p>
                </div>

                <div class="p-6">
                    {{-- Save Status --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-center p-6 rounded-xl transition-all duration-300"
                             :class="{
                                 'bg-gray-100 dark:bg-gray-700': saveState === 'idle',
                                 'bg-orange-100 dark:bg-orange-900/30 animate-pulse': saveState === 'saving',
                                 'bg-green-100 dark:bg-green-900/30': saveState === 'success',
                                 'bg-red-100 dark:bg-red-900/30': saveState === 'error'
                             }">
                            <div class="text-center">
                                <template x-if="saveState === 'idle'">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">ข้อมูลที่จะบันทึก:</p>
                                        <div class="text-left text-sm space-y-1">
                                            <p><span class="text-gray-500">NFC UID:</span> <span class="font-mono font-semibold" x-text="cardUID || '-'"></span></p>
                                            <p><span class="text-gray-500">Anti-Counterfeit Code:</span> <span class="font-mono text-xs" x-text="antiCounterfeitCode ? antiCounterfeitCode.substring(0, 16) + '...' : '-'"></span></p>
                                            <p><span class="text-gray-500">Signature:</span> <span class="font-mono text-xs" x-text="signature ? signature.substring(0, 16) + '...' : '-'"></span></p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="saveState === 'saving'">
                                    <p class="text-orange-600 dark:text-orange-400 flex items-center gap-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        กำลังบันทึก...
                                    </p>
                                </template>
                                <template x-if="saveState === 'success'">
                                    <p class="text-green-600 dark:text-green-400 flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i>
                                        บันทึกข้อมูลสำเร็จ!
                                    </p>
                                </template>
                                <template x-if="saveState === 'error'">
                                    <p class="text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-times-circle"></i>
                                        <span x-text="saveError"></span>
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            @click="saveToDatabase()"
                            :disabled="saveState === 'saving' || !cardUID || !antiCounterfeitCode"
                            class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-database"></i>
                        <span x-text="saveState === 'saving' ? 'กำลังบันทึก...' : 'บันทึกลงฐานข้อมูล'"></span>
                    </button>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        ต้องเขียนข้อมูลลงบัตรก่อนเพื่อให้ได้รหัสป้องกันปลอม
                    </p>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Card Preview --}}
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 dark:from-purple-600 dark:to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="font-semibold text-purple-100 mb-4">ข้อมูลบัตร</h3>

                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-purple-200 mb-1">เลขบัตร</div>
                        <div class="font-mono font-bold text-lg">{{ $nfcCard->card_number }}</div>
                    </div>

                    @if($nfcCard->card_name)
                        <div>
                            <div class="text-xs text-purple-200 mb-1">ชื่อบัตร</div>
                            <div class="font-semibold">{{ $nfcCard->card_name }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs text-purple-200 mb-1">ประเภท</div>
                        <div class="font-semibold">{{ $nfcCard->card_type_label }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-purple-200 mb-1">สถานะ</div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($nfcCard->status === 'active') bg-green-500
                            @elseif($nfcCard->status === 'blocked') bg-red-500
                            @else bg-gray-500 @endif">
                            {{ $nfcCard->status_label }}
                        </span>
                    </div>

                    <div>
                        <div class="text-xs text-purple-200 mb-1">ยอดเงินคงเหลือ</div>
                        <div class="text-2xl font-bold">฿{{ number_format($nfcCard->balance, 2) }}</div>
                    </div>

                    @if($nfcCard->nfc_uid)
                        <div>
                            <div class="text-xs text-purple-200 mb-1">NFC UID (ในระบบ)</div>
                            <div class="font-mono text-sm">{{ $nfcCard->nfc_uid }}</div>
                        </div>
                    @endif

                    @if($nfcCard->nfc_written_at)
                        <div>
                            <div class="text-xs text-purple-200 mb-1">เขียนข้อมูลเมื่อ</div>
                            <div class="text-sm">{{ $nfcCard->nfc_written_at->diffForHumans() }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Status Checklist --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-tasks text-blue-600"></i>
                    สถานะการดำเนินการ
                </h3>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center"
                              :class="cardUID ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                            <i :class="cardUID ? 'fas fa-check' : 'fas fa-circle'" class="text-xs"></i>
                        </span>
                        <span :class="cardUID ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'">อ่านบัตร NFC</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center"
                              :class="antiCounterfeitCode ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                            <i :class="antiCounterfeitCode ? 'fas fa-check' : 'fas fa-circle'" class="text-xs"></i>
                        </span>
                        <span :class="antiCounterfeitCode ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'">เขียนข้อมูลลงบัตร</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center
                              {{ $nfcCard->isPaired() ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                            <i class="fas {{ $nfcCard->isPaired() ? 'fa-check' : 'fa-circle' }} text-xs"></i>
                        </span>
                        <span class="{{ $nfcCard->isPaired() ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">จับคู่กับผู้ใช้</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center"
                              :class="saveState === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                            <i :class="saveState === 'success' ? 'fas fa-check' : 'fas fa-circle'" class="text-xs"></i>
                        </span>
                        <span :class="saveState === 'success' ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'">บันทึกลงระบบ</span>
                    </li>
                </ul>
            </div>

            {{-- Help Card --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-question-circle text-blue-600"></i>
                    วิธีใช้งาน
                </h3>
                <ol class="space-y-2 text-sm text-gray-700 dark:text-gray-300 list-decimal list-inside">
                    <li>กด "เริ่มอ่านบัตร NFC" แล้วแตะบัตร</li>
                    <li>รอจนได้ NFC UID</li>
                    <li>กด "เขียนข้อมูลลงบัตร" แล้วแตะบัตรอีกครั้ง</li>
                    <li>เลือกผู้ใช้และกดจับคู่</li>
                    <li>กด "บันทึกลงฐานข้อมูล"</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function nfcPairAndWrite() {
    return {
        // NFC Support
        nfcSupported: 'NDEFReader' in window,

        // Read State
        readState: 'idle', // idle, reading, success, error
        cardUID: '{{ $nfcCard->nfc_uid }}' || null,
        cardInfo: null,
        ndefReader: null,
        readAbortController: null,

        // Write State
        writeState: 'idle', // idle, writing, success, error
        writeError: '',
        selectedTemplate: 'member_card',
        writeData: {
            url: '{{ route("nfc.verify", ["card" => $nfcCard->card_number]) }}',
            customText: '',
        },

        // Anti-Counterfeit
        antiCounterfeitCode: '{{ $nfcCard->anti_counterfeit_code }}' || null,
        signature: '{{ $nfcCard->nfc_signature }}' || null,

        // Save State
        saveState: 'idle', // idle, saving, success, error
        saveError: '',

        // User Selection
        userSearch: '',
        selectedUser: null,
        users: @json($users ?? []),

        // Referral URL
        referralBaseUrl: '{{ $referralBaseUrl ?? route("register") }}',

        // Computed
        get readStatusText() {
            switch(this.readState) {
                case 'idle': return 'พร้อมอ่านบัตร';
                case 'reading': return 'กำลังรอบัตร NFC... แตะบัตรเดี๋ยวนี้';
                case 'success': return 'อ่านบัตรสำเร็จ!';
                case 'error': return 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                default: return '';
            }
        },

        get filteredUsers() {
            if (!this.userSearch) return this.users;
            const query = this.userSearch.toLowerCase();
            return this.users.filter(user =>
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query) ||
                (user.id + '').includes(query)
            );
        },

        // สร้าง Referral URL จาก member_number ของ user ที่เลือก
        getReferralUrl() {
            if (!this.selectedUser || !this.selectedUser.member_number) {
                return this.referralBaseUrl;
            }
            return `${this.referralBaseUrl}?ref=${this.selectedUser.member_number}`;
        },

        // Methods
        async startReading() {
            if (!this.nfcSupported) {
                alert('เบราว์เซอร์ไม่รองรับ Web NFC');
                return;
            }

            try {
                this.readState = 'reading';
                this.ndefReader = new NDEFReader();
                this.readAbortController = new AbortController();

                await this.ndefReader.scan({ signal: this.readAbortController.signal });

                this.ndefReader.addEventListener('reading', ({ serialNumber, message }) => {
                    console.log('NFC Reading:', serialNumber, message);

                    // Get UID
                    this.cardUID = serialNumber;

                    // Get card info
                    this.cardInfo = {
                        cardType: this.detectCardType(serialNumber),
                        memorySize: null,
                        isWritable: true,
                        recordsCount: message.records.length,
                    };

                    this.readState = 'success';

                    // Stop scanning after successful read
                    this.stopReading();
                });

                this.ndefReader.addEventListener('readingerror', () => {
                    this.readState = 'error';
                    console.error('NFC reading error');
                });

            } catch (error) {
                console.error('NFC Error:', error);
                this.readState = 'error';

                if (error.name === 'NotAllowedError') {
                    alert('กรุณาอนุญาตการใช้งาน NFC');
                } else if (error.name === 'NotSupportedError') {
                    alert('อุปกรณ์ไม่รองรับ NFC');
                }
            }
        },

        stopReading() {
            if (this.readAbortController) {
                this.readAbortController.abort();
                this.readAbortController = null;
            }
            if (this.readState === 'reading') {
                this.readState = 'idle';
            }
        },

        detectCardType(serialNumber) {
            // Simple detection based on UID length
            const uidLength = serialNumber.replace(/:/g, '').length / 2;
            if (uidLength === 4) return 'MIFARE_CLASSIC';
            if (uidLength === 7) return 'NTAG213/215/216';
            return 'Unknown';
        },

        async generateAndWrite() {
            if (!this.nfcSupported || !this.cardUID) {
                alert('กรุณาอ่านบัตรก่อน');
                return;
            }

            try {
                this.writeState = 'writing';

                // Step 1: Generate anti-counterfeit code from server
                const codeResponse = await fetch('{{ route("admin.nfc-cards.generate-anti-counterfeit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        card_number: '{{ $nfcCard->card_number }}',
                        uid: this.cardUID,
                    }),
                });

                const codeData = await codeResponse.json();

                if (!codeData.success) {
                    throw new Error(codeData.message || 'Failed to generate code');
                }

                this.antiCounterfeitCode = codeData.code;
                this.signature = codeData.signature;

                // Step 2: Write to NFC card
                const ndefWriter = new NDEFReader();

                // Build NDEF records
                const records = [];

                // 🔗 URL Record - Referral Link (เมื่อแตะบัตรจะไปหน้าชวนคน)
                // ใช้ referral URL ถ้าเลือก user แล้ว, ไม่งั้นใช้ verification URL
                let primaryUrl;
                if (this.selectedUser && this.selectedUser.member_number) {
                    // Referral URL - ไปหน้าสมัครสมาชิกพร้อม referral code
                    primaryUrl = this.getReferralUrl();
                } else {
                    // Verification URL - ตรวจสอบบัตร
                    primaryUrl = `{{ route("nfc.verify", ["card" => $nfcCard->card_number]) }}?code=${this.antiCounterfeitCode.substring(0, 16)}`;
                }
                records.push({
                    recordType: 'url',
                    data: primaryUrl,
                });

                // 👤 นามบัตรของลูกค้า (ถ้าเลือก user แล้ว)
                if (this.selectedUser) {
                    // ชื่อ
                    records.push({
                        recordType: 'text',
                        data: `NAME:${this.selectedUser.name}`,
                        lang: 'th',
                    });

                    // Member Number
                    if (this.selectedUser.member_number) {
                        records.push({
                            recordType: 'text',
                            data: `MEMBER:${this.selectedUser.member_number}`,
                            lang: 'en',
                        });
                    }

                    // เบอร์โทร
                    if (this.selectedUser.phone) {
                        records.push({
                            recordType: 'text',
                            data: `TEL:${this.selectedUser.phone}`,
                            lang: 'en',
                        });
                    }

                    // อีเมล
                    if (this.selectedUser.email) {
                        records.push({
                            recordType: 'text',
                            data: `EMAIL:${this.selectedUser.email}`,
                            lang: 'en',
                        });
                    }

                    // LINE ID
                    if (this.selectedUser.line_id) {
                        records.push({
                            recordType: 'text',
                            data: `LINE:${this.selectedUser.line_id}`,
                            lang: 'en',
                        });
                    }
                }

                // 🔐 Card Info & Anti-Counterfeit
                records.push({
                    recordType: 'text',
                    data: `TP-NFC:${this.cardUID}:${this.antiCounterfeitCode.substring(0, 8)}`,
                    lang: 'en',
                });

                // Custom text if provided
                if (this.writeData.customText) {
                    records.push({
                        recordType: 'text',
                        data: this.writeData.customText,
                        lang: 'th',
                    });
                }

                await ndefWriter.write({ records });

                this.writeState = 'success';

            } catch (error) {
                console.error('Write Error:', error);
                this.writeState = 'error';
                this.writeError = error.message || 'เกิดข้อผิดพลาดในการเขียน';
            }
        },

        async saveToDatabase() {
            if (!this.cardUID || !this.antiCounterfeitCode) {
                alert('กรุณาอ่านและเขียนบัตรก่อน');
                return;
            }

            try {
                this.saveState = 'saving';

                const response = await fetch('{{ route("admin.nfc-cards.save-nfc-uid", $nfcCard) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        nfc_uid: this.cardUID,
                        anti_counterfeit_code: this.antiCounterfeitCode,
                        signature: this.signature,
                    }),
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to save');
                }

                this.saveState = 'success';

                // Also save card info if available
                if (this.cardInfo) {
                    await fetch('{{ route("admin.nfc-cards.save-card-info", $nfcCard) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            card_type_detected: this.cardInfo.cardType,
                            memory_size: this.cardInfo.memorySize,
                            ndef_writeable: this.cardInfo.isWritable,
                            ndef_records_count: this.cardInfo.recordsCount,
                            nfc_uid: this.cardUID,
                        }),
                    });
                }

            } catch (error) {
                console.error('Save Error:', error);
                this.saveState = 'error';
                this.saveError = error.message || 'เกิดข้อผิดพลาดในการบันทึก';
            }
        },
    };
}
</script>
@endpush
@endsection
