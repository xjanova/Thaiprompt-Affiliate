@extends('layouts.admin-v3')

@section('title', 'รายละเอียดเทมเพลต')

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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $template->name ?? 'รายละเอียดเทมเพลต' }}</h1>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}"
                   class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span data-translate>แก้ไขเทมเพลต</span>
                </a>
                <a href="{{ route('admin.bot-automation.templates.index') }}"
                   class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span data-translate>กลับไปหน้ารายการ</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content (Left Column - 2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Template Information Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ข้อมูลเทมเพลต</h2>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                {{ isset($template->status) && $template->status == 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300' }}">
                                <span data-translate>{{ isset($template->status) && $template->status == 'active' ? 'กำลังใช้งาน' : ucfirst($template->status ?? 'N/A') }}</span>
                            </span>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                <span data-translate>{{ isset($template->category) ? ucfirst($template->category) : 'N/A' }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>ชื่อเทมเพลต</h3>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $template->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>ภาษา</h3>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ isset($template->language) ? strtoupper($template->language) : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>คำอธิบาย</h3>
                            <p class="text-gray-900 dark:text-white">{{ $template->description ?? 'ไม่มีคำอธิบาย' }}</p>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>แท็ก</h3>
                            @if(isset($template->tags) && $template->tags)
                                <div class="flex flex-wrap gap-2">
                                    @foreach(explode(',', $template->tags) as $tag)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300">
                                            {{ trim($tag) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีแท็ก</p>
                            @endif
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>ข้อความต้อนรับ</h3>
                                    <p class="text-gray-900 dark:text-white">{{ $template->welcome_message ?? 'ไม่ได้ตั้งค่า' }}</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>ข้อความสำรอง</h3>
                                    <p class="text-gray-900 dark:text-white">{{ $template->fallback_message ?? 'ไม่ได้ตั้งค่า' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Flows Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>โฟลว์เทมเพลต</h2>
                    </div>
                    <div class="p-6">
                        @forelse($template->flows ?? [] as $index => $flow)
                        <div class="mb-4 last:mb-0 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border-l-4 border-purple-600 p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">
                                <span data-translate>โฟลว์</span> {{ $index + 1 }}
                            </h3>
                            <div class="mb-3">
                                <strong class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>คำสำคัญทริกเกอร์:</strong>
                                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">{{ $flow->keywords ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <strong class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>การตอบกลับ:</strong>
                                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">{{ $flow->response ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full mb-4">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีโฟลว์ที่กำหนดค่าสำหรับเทมเพลตนี้</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Usage History Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ประวัติการใช้งาน</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>วันที่</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ผู้ใช้</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>บอท</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($usageHistory ?? [] as $usage)
                                <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ isset($usage->created_at) ? $usage->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $usage->user_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $usage->bot_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                                            {{ $usage->status == 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300' }}">
                                            <span data-translate>{{ ucfirst($usage->status ?? 'N/A') }}</span>
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>
                                        ไม่มีประวัติการใช้งาน
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right Column - 1/3) -->
            <div class="space-y-6">
                <!-- Quick Actions Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>การดำเนินการด่วน</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <a href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}"
                               class="flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 rounded-xl transition text-gray-900 dark:text-white">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span data-translate>แก้ไขเทมเพลต</span>
                            </a>
                            <a href="#" onclick="duplicateTemplate(); return false;"
                               class="flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 rounded-xl transition text-gray-900 dark:text-white">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span data-translate>คัดลอกเทมเพลต</span>
                            </a>
                            <a href="#" onclick="exportTemplate(); return false;"
                               class="flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 rounded-xl transition text-gray-900 dark:text-white">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span data-translate>ส่งออกเทมเพลต</span>
                            </a>
                            <a href="#" onclick="testTemplate(); return false;"
                               class="flex items-center gap-3 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 rounded-xl transition text-gray-900 dark:text-white">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                <span data-translate>ทดสอบเทมเพลต</span>
                            </a>
                            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-600 my-2"></div>
                            <a href="#" onclick="deleteTemplate(); return false;"
                               class="flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition text-red-600 dark:text-red-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span data-translate>ลบเทมเพลต</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-md overflow-hidden text-white">
                    <div class="px-6 py-4 border-b border-white/20">
                        <h2 class="text-xl font-bold" data-translate>สถิติ</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold" data-translate>การใช้งานทั้งหมด:</span>
                                <span class="px-3 py-1 glass-fusion rounded-full font-bold">{{ $template->uses_count ?? '0' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold" data-translate>คะแนน:</span>
                                <div class="flex items-center gap-2">
                                    <span>{{ number_format($template->rating ?? 0, 1) }}/5.0</span>
                                    <div class="flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= ($template->rating ?? 0) ? 'text-yellow-300' : 'text-white/30' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold" data-translate>โฟลว์ทั้งหมด:</span>
                                <span class="px-3 py-1 glass-fusion rounded-full font-bold">{{ count($template->flows ?? []) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold" data-translate>สาธารณะ:</span>
                                <span>{{ isset($template->is_public) && $template->is_public ? 'ใช่' : 'ไม่' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold" data-translate>แนะนำ:</span>
                                <span>{{ isset($template->is_featured) && $template->is_featured ? 'ใช่' : 'ไม่' }}</span>
                            </div>
                            <div class="pt-4 border-t border-white/20">
                                <div class="mb-2">
                                    <strong data-translate>สร้างเมื่อ:</strong><br>
                                    <span class="text-sm opacity-90">{{ isset($template->created_at) ? $template->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                </div>
                                <div>
                                    <strong data-translate>อัพเดทล่าสุด:</strong><br>
                                    <span class="text-sm opacity-90">{{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ตัวอย่าง</h2>
                    </div>
                    <div class="p-6">
                        <div class="bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl p-4">
                            <div class="mb-2">
                                <div class="inline-block max-w-xs bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl px-4 py-2">
                                    {{ $template->welcome_message ?? 'ข้อความต้อนรับ...' }}
                                </div>
                            </div>
                            <div class="text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 italic mt-4">
                                <span data-translate>โหมดตัวอย่าง</span>
                            </div>
                        </div>
                    </div>
                </div>
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
function duplicateTemplate() {
    if (confirm('คุณต้องการคัดลอกเทมเพลตนี้หรือไม่?')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname + '/duplicate';

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

function exportTemplate() {
    // เปิดหน้าต่างใหม่เพื่อดาวน์โหลดไฟล์
    window.location.href = window.location.pathname + '/export';
}

async function testTemplate() {
    try {
        // แสดง loading
        const loadingToast = document.createElement('div');
        loadingToast.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-xl shadow-lg z-50';
        loadingToast.textContent = 'กำลังทดสอบเทมเพลต...';
        document.body.appendChild(loadingToast);

        // ส่ง request
        const response = await fetch(window.location.pathname + '/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                platform: 'line',
                test_data: {}
            })
        });

        const data = await response.json();

        // ลบ loading toast
        loadingToast.remove();

        if (data.success) {
            // แสดงผลลัพธ์
            alert('✅ ทดสอบสำเร็จ!\n\nเนื้อหา:\n' + data.preview.content);
        } else {
            alert('❌ การทดสอบล้มเหลว: ' + (data.message || 'เกิดข้อผิดพลาด'));
        }
    } catch (error) {
        console.error('Test error:', error);
        alert('❌ เกิดข้อผิดพลาดในการทดสอบ');
    }
}

function deleteTemplate() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบเทมเพลตนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

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
            location.reload();
        }
    });
});
</script>
@endsection
