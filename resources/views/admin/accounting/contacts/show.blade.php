@extends('layouts.admin-v3')

@section('title', 'รายละเอียดผู้ติดต่อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header with Accounting Theme -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <!-- Language Switcher Component -->
        <div class="absolute top-4 right-4 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open"
                        class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 flex items-center gap-2 shadow-lg border border-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <span x-text="{ th: 'ไทย 🇹🇭', en: 'English 🇬🇧', zh: '中文 🇨🇳', ja: '日本語 🇯🇵' }[language]"></span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 rounded-xl shadow-2xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;">
                    <button @click="language = 'th'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">ไทย 🇹🇭</button>
                    <button @click="language = 'en'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">English 🇬🇧</button>
                    <button @click="language = 'zh'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">中文 🇨🇳</button>
                    <button @click="language = 'ja'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">日本語 🇯🇵</button>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <!-- Back Button & Title -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.accounting.contacts.index') }}"
                       class="group p-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all duration-200 border border-white/20">
                        <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">{{ $contact->name }}</h1>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl text-sm font-medium border border-white/20" data-translate>
                                รายละเอียดผู้ติดต่อ
                            </span>
                        </div>
                        @if($contact->company_name)
                        <p class="text-white/90 text-lg font-medium">{{ $contact->company_name }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.accounting.contacts.statement', $contact) }}"
                   class="group px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ใบแจ้งยอด</span>
                </a>

                @if(auth()->user()->hasPermission('accounting.edit_contacts') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.contacts.edit', $contact) }}"
                   class="group px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                    <span data-translate>แก้ไข</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('accounting.delete_contacts') || auth()->user()->isSuperAdmin())
                <form action="{{ route('admin.accounting.contacts.destroy', $contact) }}" method="POST" class="inline"
                      onsubmit="return confirm('ยืนยันการลบผู้ติดต่อนี้?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="group px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span data-translate>ลบ</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Contact Details (Left Column) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <!-- Card Header -->
                <div class="relative bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>ข้อมูลพื้นฐาน</h2>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contact Name -->
                        <div class="group/item">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ชื่อผู้ติดต่อ</label>
                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 group-hover/item:border-emerald-300 dark:group-hover/item:border-emerald-600 transition-colors">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $contact->name }}</p>
                            </div>
                        </div>

                        <!-- Contact Type -->
                        <div class="group/item">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ประเภท</label>
                            <div class="p-4 rounded-xl border-2
                                @if($contact->is_customer && $contact->is_vendor)
                                    bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-700
                                @elseif($contact->is_customer)
                                    bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-700
                                @else
                                    bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 border-orange-200 dark:border-orange-700
                                @endif">
                                <p class="text-lg font-semibold flex items-center gap-2">
                                    @if($contact->is_customer && $contact->is_vendor)
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-purple-900 dark:text-purple-100" data-translate>ทั้งสองอย่าง</span>
                                    @elseif($contact->is_customer)
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-blue-900 dark:text-blue-100" data-translate>ลูกค้า</span>
                                    @else
                                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-orange-900 dark:text-orange-100" data-translate>ผู้ขาย</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($contact->company_name)
                    <div class="group/item">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ชื่อบริษัท</label>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 group-hover/item:border-emerald-300 dark:group-hover/item:border-emerald-600 transition-colors">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $contact->company_name }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Email -->
                        <div class="group/item">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>อีเมล</label>
                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 group-hover/item:border-emerald-300 dark:group-hover/item:border-emerald-600 transition-colors">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                <p class="text-gray-900 dark:text-white">{{ $contact->email ?? '-' }}</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="group/item">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>โทรศัพท์</label>
                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 group-hover/item:border-emerald-300 dark:group-hover/item:border-emerald-600 transition-colors">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                <p class="text-gray-900 dark:text-white">{{ $contact->phone ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($contact->tax_id)
                    <div class="group/item">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>เลขประจำตัวผู้เสียภาษี</label>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 group-hover/item:border-emerald-300 dark:group-hover/item:border-emerald-600 transition-colors">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-gray-900 dark:text-white font-mono">{{ $contact->tax_id }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Status -->
                    <div class="group/item">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>สถานะ</label>
                        <div class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold
                            {{ $contact->is_active ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            @if($contact->is_active)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span data-translate>ใช้งาน</span>
                            @else
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span data-translate>ไม่ใช้งาน</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <div class="relative bg-gradient-to-r from-teal-500 to-cyan-600 dark:from-teal-700 dark:to-cyan-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>ที่อยู่</h2>
                    </div>
                </div>
                <div class="p-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                        <p class="text-gray-900 dark:text-white leading-relaxed">
                            {{ $contact->address ?? '-' }}<br>
                            @if($contact->district || $contact->province || $contact->postal_code)
                                {{ $contact->district }} {{ $contact->province }} {{ $contact->postal_code }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if($contact->notes)
            <!-- Notes Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <div class="relative bg-gradient-to-r from-amber-500 to-orange-600 dark:from-amber-700 dark:to-orange-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>หมายเหตุ</h2>
                    </div>
                </div>
                <div class="p-6">
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
                        <p class="text-gray-900 dark:text-white leading-relaxed">{{ $contact->notes }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Stats & Recent Activity (Right Column) -->
        <div class="space-y-6">
            <!-- Balance Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl hover:scale-105 transition-all duration-300 overflow-hidden">
                <div class="relative bg-gradient-to-r {{ $contact->balance > 0 ? 'from-red-500 to-red-600 dark:from-red-700 dark:to-red-800' : 'from-green-500 to-emerald-600 dark:from-green-700 dark:to-emerald-800' }} px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>ยอดค้างชำระ</h2>
                    </div>
                </div>
                <div class="p-8 text-center">
                    <div class="text-5xl font-bold {{ $contact->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        ฿{{ number_format($contact->balance ?? 0, 2) }}
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <div class="relative bg-gradient-to-r from-blue-500 to-indigo-600 dark:from-blue-700 dark:to-indigo-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>สถิติ</h2>
                    </div>
                </div>
                <div class="p-6 space-y-6">
                    @if($contact->is_customer)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                        <label class="block text-sm font-medium text-blue-700 dark:text-blue-300 mb-2" data-translate>ใบแจ้งหนี้ทั้งหมด</label>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['invoices'] ?? 0) }}</p>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border-2 border-green-200 dark:border-green-700">
                        <label class="block text-sm font-medium text-green-700 dark:text-green-300 mb-2" data-translate>ยอดขายทั้งหมด</label>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($stats['total_sales'] ?? 0, 2) }}</p>
                    </div>
                    @endif

                    @if($contact->is_vendor)
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border-2 border-orange-200 dark:border-orange-700">
                        <label class="block text-sm font-medium text-orange-700 dark:text-orange-300 mb-2" data-translate>รายจ่ายทั้งหมด</label>
                        <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">฿{{ number_format($stats['total_purchases'] ?? 0, 2) }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Additional Info Card -->
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <div class="relative bg-gradient-to-r from-gray-600 to-gray-700 dark:from-gray-700 dark:to-gray-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-white" data-translate>ข้อมูลเพิ่มเติม</h2>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1" data-translate>สร้างเมื่อ</label>
                        <p class="text-gray-900 dark:text-white font-semibold">{{ $contact->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1" data-translate>แก้ไขล่าสุด</label>
                        <p class="text-gray-900 dark:text-white font-semibold">{{ $contact->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
