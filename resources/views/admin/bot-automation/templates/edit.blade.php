@extends('layouts.admin-v3')

@section('title', 'แก้ไขเทมเพลตบอท')

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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>แก้ไขเทมเพลตบอท</h1>
            <a href="{{ route('admin.bot-automation.templates.index') }}"
               class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปหน้ารายการ</span>
            </a>
        </div>

        <form action="{{ route('admin.bot-automation.templates.update', $template->id ?? 0) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content (Left Column - 2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information Card -->
                    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ข้อมูลพื้นฐาน</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Template Name -->
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>ชื่อเทมเพลต</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                       id="name" name="name" value="{{ old('name', $template->name ?? '') }}" required>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>คำอธิบาย</span> <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror"
                                    id="description" name="description" rows="3" required>{{ old('description', $template->description ?? '') }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Category & Language -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                        <span data-translate>หมวดหมู่</span> <span class="text-red-500">*</span>
                                    </label>
                                    <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('category') border-red-500 @enderror"
                                            id="category" name="category" required>
                                        <option value=""><span data-translate>เลือกหมวดหมู่</span></option>
                                        <option value="sales" {{ old('category', $template->category ?? '') == 'sales' ? 'selected' : '' }}><span data-translate>ขาย</span></option>
                                        <option value="support" {{ old('category', $template->category ?? '') == 'support' ? 'selected' : '' }}><span data-translate>ซัพพอร์ต</span></option>
                                        <option value="marketing" {{ old('category', $template->category ?? '') == 'marketing' ? 'selected' : '' }}><span data-translate>การตลาด</span></option>
                                        <option value="education" {{ old('category', $template->category ?? '') == 'education' ? 'selected' : '' }}><span data-translate>การศึกษา</span></option>
                                        <option value="ecommerce" {{ old('category', $template->category ?? '') == 'ecommerce' ? 'selected' : '' }}><span data-translate>อีคอมเมิร์ซ</span></option>
                                    </select>
                                    @error('category')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="language" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                        <span data-translate>ภาษา</span> <span class="text-red-500">*</span>
                                    </label>
                                    <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('language') border-red-500 @enderror"
                                            id="language" name="language" required>
                                        <option value="en" {{ old('language', $template->language ?? '') == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="th" {{ old('language', $template->language ?? '') == 'th' ? 'selected' : '' }}>Thai</option>
                                        <option value="ja" {{ old('language', $template->language ?? '') == 'ja' ? 'selected' : '' }}>Japanese</option>
                                        <option value="zh" {{ old('language', $template->language ?? '') == 'zh' ? 'selected' : '' }}>Chinese</option>
                                    </select>
                                    @error('language')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label for="tags" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>แท็ก (คั่นด้วยคอมม่า)</span>
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('tags') border-red-500 @enderror"
                                       id="tags" name="tags" value="{{ old('tags', $template->tags ?? '') }}"
                                       placeholder="เช่น บริการลูกค้า, คำถามที่พบบ่อย, อัตโนมัติ">
                                @error('tags')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Template Configuration Card -->
                    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>การกำหนดค่าเทมเพลต</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Welcome Message -->
                            <div>
                                <label for="welcome_message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>ข้อความต้อนรับ</span>
                                </label>
                                <textarea
                                    class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('welcome_message') border-red-500 @enderror"
                                    id="welcome_message" name="welcome_message" rows="3">{{ old('welcome_message', $template->welcome_message ?? '') }}</textarea>
                                @error('welcome_message')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Fallback Message -->
                            <div>
                                <label for="fallback_message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>ข้อความสำรอง</span>
                                </label>
                                <textarea
                                    class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('fallback_message') border-red-500 @enderror"
                                    id="fallback_message" name="fallback_message" rows="3">{{ old('fallback_message', $template->fallback_message ?? '') }}</textarea>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>ข้อความที่จะแสดงเมื่อบอทไม่เข้าใจคำถามของผู้ใช้</p>
                                @error('fallback_message')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Template Flows -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                    <span data-translate>โฟลว์เทมเพลต</span>
                                </label>
                                <div id="flowsContainer" class="space-y-4">
                                    @forelse($template->flows ?? [] as $index => $flow)
                                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <strong class="text-gray-900 dark:text-white"><span data-translate>โฟลว์</span> {{ $index + 1 }}</strong>
                                            <button type="button"
                                                    class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm rounded-xl transition"
                                                    onclick="this.closest('.bg-gradient-to-r').remove()">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>คำสำคัญทริกเกอร์</label>
                                                <input type="text"
                                                       class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                       name="flows[{{ $index }}][keywords]" value="{{ $flow->keywords ?? '' }}">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>การตอบกลับ</label>
                                                <textarea class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                          name="flows[{{ $index }}][response]" rows="2">{{ $flow->response ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-4">
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>คำสำคัญทริกเกอร์</label>
                                                <input type="text"
                                                       class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                       name="flows[0][keywords]" placeholder="เช่น สวัสดี, ไง, เริ่ม">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>การตอบกลับ</label>
                                                <textarea class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                          name="flows[0][response]" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                                <button type="button"
                                        class="mt-4 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition flex items-center gap-2"
                                        onclick="addFlow()">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span data-translate>เพิ่มโฟลว์</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Right Column - 1/3) -->
                <div class="space-y-6">
                    <!-- Publishing Options Card -->
                    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ตัวเลือกการเผยแพร่</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>สถานะ</span> <span class="text-red-500">*</span>
                                </label>
                                <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('status') border-red-500 @enderror"
                                        id="status" name="status" required>
                                    <option value="draft" {{ old('status', $template->status ?? '') == 'draft' ? 'selected' : '' }}><span data-translate>แบบร่าง</span></option>
                                    <option value="active" {{ old('status', $template->status ?? '') == 'active' ? 'selected' : '' }}><span data-translate>กำลังใช้งาน</span></option>
                                    <option value="archived" {{ old('status', $template->status ?? '') == 'archived' ? 'selected' : '' }}><span data-translate>เก็บถาวร</span></option>
                                </select>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Is Public -->
                            <div>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="w-5 h-5 text-purple-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded focus:ring-purple-500 focus:ring-2"
                                           id="is_public" name="is_public" value="1" {{ old('is_public', $template->is_public ?? false) ? 'checked' : '' }}>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>เผยแพร่สาธารณะ</span>
                                </label>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>อนุญาตให้ผู้ใช้อื่นใช้เทมเพลตนี้</p>
                            </div>

                            <!-- Is Featured -->
                            <div>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="w-5 h-5 text-purple-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded focus:ring-purple-500 focus:ring-2"
                                           id="is_featured" name="is_featured" value="1" {{ old('is_featured', $template->is_featured ?? false) ? 'checked' : '' }}>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>เทมเพลตแนะนำ</span>
                                </label>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-600 pt-6">
                                <!-- Submit Button -->
                                <button type="submit"
                                        class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-md transition-all duration-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <span data-translate>อัพเดทเทมเพลต</span>
                                </button>

                                <!-- Cancel Button -->
                                <a href="{{ route('admin.bot-automation.templates.index') }}"
                                   class="mt-3 w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl shadow-md transition-all duration-200 flex items-center justify-center gap-2">
                                    <span data-translate>ยกเลิก</span>
                                </a>

                                <!-- Delete Button -->
                                <button type="button"
                                        onclick="confirmDelete()"
                                        class="mt-3 w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-md transition-all duration-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span data-translate>ลบเทมเพลต</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Template Statistics Card -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-md overflow-hidden text-white">
                        <div class="px-6 py-4 border-b border-white/20">
                            <h2 class="text-xl font-bold" data-translate>สถิติเทมเพลต</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold" data-translate>การใช้งาน:</span>
                                    <span class="px-3 py-1 glass-fusion rounded-full font-bold">{{ $template->uses_count ?? '0' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold" data-translate>คะแนน:</span>
                                    <span>{{ number_format($template->rating ?? 0, 1) }}/5.0</span>
                                </div>
                                <div class="pt-3 border-t border-white/20">
                                    <div class="mb-2">
                                        <strong data-translate>สร้างเมื่อ:</strong><br>
                                        <span class="text-sm opacity-90">{{ isset($template->created_at) ? $template->created_at->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <strong data-translate>อัพเดทล่าสุด:</strong><br>
                                        <span class="text-sm opacity-90">{{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Hidden Delete Form -->
        <form id="deleteForm" action="{{ route('admin.bot-automation.templates.destroy', $template->id ?? 0) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
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

// Flow Management
let flowCount = {{ count($template->flows ?? []) }};

function addFlow() {
    const container = document.getElementById('flowsContainer');
    const flowHtml = `
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-4">
            <div class="flex justify-between items-center mb-4">
                <strong class="text-gray-900 dark:text-white"><span data-translate>โฟลว์</span> ${flowCount + 1}</strong>
                <button type="button"
                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm rounded-xl transition"
                        onclick="this.closest('.bg-gradient-to-r').remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>คำสำคัญทริกเกอร์</label>
                    <input type="text"
                           class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                           name="flows[${flowCount}][keywords]" placeholder="เช่น ช่วยเหลือ, ซัพพอร์ต">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1" data-translate>การตอบกลับ</label>
                    <textarea class="w-full px-4 py-2 glass-fusion dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                              name="flows[${flowCount}][response]" rows="2"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', flowHtml);
    flowCount++;
}

function confirmDelete() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบเทมเพลตนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        document.getElementById('deleteForm').submit();
    }
}

// เฝ้าดูการเปลี่ยนภาษา
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        } else {
            location.reload();
        }
    });
});
</script>
@endsection
