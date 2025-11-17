@extends('layouts.admin-v3')

@section('title', 'เทมเพลตบอทอัตโนมัติ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="absolute top-0 right-0 z-10 mt-4 mr-4">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 glass-fusion dark:bg-gray-800 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl shadow-sm hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:border-gray-700 py-2 z-50" border border-white/20 dark:border-white/10
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>เทมเพลตบอทอัตโนมัติ</h1>
            <a href="{{ route('admin.bot-automation.templates.create') }}"
               class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span data-translate>สร้างเทมเพลต</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Card 1: Total Templates -->
            <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-md p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-semibold uppercase opacity-90 mb-1" data-translate>เทมเพลตทั้งหมด</div>
                        <div class="text-3xl font-bold">{{ $totalTemplates ?? '0' }}</div>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 2: Active Templates -->
            <div class="bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl shadow-md p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-semibold uppercase opacity-90 mb-1" data-translate>กำลังใช้งาน</div>
                        <div class="text-3xl font-bold">{{ $activeTemplates ?? '0' }}</div>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Uses -->
            <div class="bg-gradient-to-br from-purple-600 to-pink-400 rounded-xl shadow-md p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-semibold uppercase opacity-90 mb-1" data-translate>การใช้งานทั้งหมด</div>
                        <div class="text-3xl font-bold">{{ $totalUses ?? '0' }}</div>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 4: Categories -->
            <div class="bg-gradient-to-br from-pink-600 to-purple-400 rounded-xl shadow-md p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-semibold uppercase opacity-90 mb-1" data-translate>หมวดหมู่</div>
                        <div class="text-3xl font-bold">{{ $totalCategories ?? '0' }}</div>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Library Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>คลังเทมเพลต</h2>
            </div>

            <div class="p-6">
                <!-- Filters Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text"
                               class="w-full px-4 py-2 pl-10 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="ค้นหาเทมเพลต..."
                               id="searchTemplates">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    <!-- Category Filter -->
                    <select class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            id="filterCategory">
                        <option value=""><span data-translate>หมวดหมู่ทั้งหมด</span></option>
                        <option value="sales"><span data-translate>ขาย</span></option>
                        <option value="support"><span data-translate>ซัพพอร์ต</span></option>
                        <option value="marketing"><span data-translate>การตลาด</span></option>
                        <option value="education"><span data-translate>การศึกษา</span></option>
                        <option value="ecommerce"><span data-translate>อีคอมเมิร์ซ</span></option>
                    </select>

                    <!-- Status Filter -->
                    <select class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            id="filterStatus">
                        <option value=""><span data-translate>สถานะทั้งหมด</span></option>
                        <option value="active"><span data-translate>กำลังใช้งาน</span></option>
                        <option value="draft"><span data-translate>แบบร่าง</span></option>
                        <option value="archived"><span data-translate>เก็บถาวร</span></option>
                    </select>

                    <!-- Sort By -->
                    <select class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            id="sortBy">
                        <option value="newest"><span data-translate>ล่าสุดก่อน</span></option>
                        <option value="popular"><span data-translate>ยอดนิยม</span></option>
                        <option value="name"><span data-translate>ชื่อ (ก-ฮ)</span></option>
                    </select>
                </div>

                <!-- Templates Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($templates ?? [] as $template)
                    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-xl transition-all duration-200 overflow-hidden" border border-white/20 dark:border-white/10>
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex-1">{{ $template->name ?? 'N/A' }}</h3>
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open"
                                            class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="open"
                                         @click.away="open = false"
                                         class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:border-gray-700 py-2 z-10" border border-white/20 dark:border-white/10
                                         style="display: none;">
                                        <a href="{{ route('admin.bot-automation.templates.show', $template->id ?? 0) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            <span data-translate>ดู</span>
                                        </a>
                                        <a href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span data-translate>แก้ไข</span>
                                        </a>
                                        <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 my-1"></div>
                                        <a href="#" onclick="duplicateTemplate({{ $template->id }}); return false;"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span data-translate>คัดลอก</span>
                                        </a>
                                        <a href="#" onclick="exportTemplate({{ $template->id }}); return false;"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            <span data-translate>ส่งออก</span>
                                        </a>
                                        <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 my-1"></div>
                                        <a href="#" onclick="deleteTemplate({{ $template->id }}); return false;"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span data-translate>ลบ</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    {{ $template->status == 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300' }}">
                                    <span data-translate>{{ $template->status == 'active' ? 'กำลังใช้งาน' : ucfirst($template->status ?? 'N/A') }}</span>
                                </span>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    <span data-translate>{{ ucfirst($template->category ?? 'N/A') }}</span>
                                </span>
                            </div>

                            <!-- Description -->
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-4 line-clamp-2">
                                {{ Str::limit($template->description ?? '', 100) }}
                            </p>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-4 mb-4 pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-1" data-translate>การใช้งาน</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $template->uses_count ?? '0' }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-1" data-translate>คะแนน</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($template->rating ?? 0, 1) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-1" data-translate>โฟลว์</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $template->flows_count ?? '0' }}</div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <span data-translate>อัพเดท</span> {{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}
                                </div>
                                <a href="{{ route('admin.bot-automation.templates.show', $template->id ?? 0) }}"
                                   class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-sm font-semibold rounded-xl transition-all duration-200">
                                    <span data-translate>ดูรายละเอียด</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <!-- Empty State -->
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-4" data-translate>ไม่มีเทมเพลต</p>
                        <a href="{{ route('admin.bot-automation.templates.create') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl shadow-md transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span data-translate>สร้างเทมเพลตแรกของคุณ</span>
                        </a>
                    </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if(isset($templates) && method_exists($templates, 'links'))
                <div class="mt-6">
                    {{ $templates->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Google Translate Script -->
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        includedLanguages: 'th,en,zh-CN,ja',
        autoDisplay: false
    }, 'google_translate_element');
}

// ฟังก์ชันแปลภาษาแบบอัตโนมัติ
function translatePage(lang) {
    const elements = document.querySelectorAll('[data-translate]');
    elements.forEach(element => {
        const text = element.textContent || element.innerText;
        if (text && lang !== 'th') {
            // ใช้ Google Translate API
            fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=th&tl=${lang}&dt=t&q=${encodeURIComponent(text)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data[0] && data[0][0] && data[0][0][0]) {
                        element.textContent = data[0][0][0];
                    }
                })
                .catch(error => console.error('Translation error:', error));
        }
    });
}

// ฟังก์ชันจัดการเทมเพลต
function duplicateTemplate(id) {
    if (confirm('คุณต้องการคัดลอกเทมเพลตนี้หรือไม่?')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/bot-automation/templates/${id}/duplicate`;

        // เพิ่ม CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function exportTemplate(id) {
    // เปิดหน้าต่างใหม่เพื่อดาวน์โหลดไฟล์
    window.location.href = `/admin/bot-automation/templates/${id}/export`;
}

function deleteTemplate(id) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบเทมเพลตนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/bot-automation/templates/${id}`;

        // เพิ่ม CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);

        // เพิ่ม method spoofing สำหรับ DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// เฝ้าดูการเปลี่ยนภาษา
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        } else {
            location.reload(); // โหลดหน้าใหม่เพื่อกลับไปใช้ภาษาไทย
        }
    });
});
</script>
@endsection
