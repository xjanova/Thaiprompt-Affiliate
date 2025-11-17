@extends('layouts.admin-v3')

@section('title', 'แดชบอร์ดซัพพอร์ตบอท')

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
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>
                แดชบอร์ดซัพพอร์ตบอท
            </h1>
            <a href="{{ route('admin.bot-automation.support.tickets.index') }}"
               class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
                <span data-translate>ดูทิกเก็ตทั้งหมด</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Card 1: Total Tickets -->
            <div class="bg-gradient-to-br from-blue-500 to-cyan-500 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider opacity-90 mb-2" data-translate>
                            ทิกเก็ตทั้งหมด
                        </p>
                        <p class="text-3xl font-bold">{{ $totalTickets ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-full p-4" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 2: Open Tickets -->
            <div class="bg-gradient-to-br from-cyan-500 to-blue-500 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider opacity-90 mb-2" data-translate>
                            ทิกเก็ตที่เปิดอยู่
                        </p>
                        <p class="text-3xl font-bold">{{ $openTickets ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-full p-4" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 3: Resolved Today -->
            <div class="bg-gradient-to-br from-blue-600 to-cyan-400 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider opacity-90 mb-2" data-translate>
                            แก้ไขวันนี้
                        </p>
                        <p class="text-3xl font-bold">{{ $resolvedToday ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-full p-4" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 4: Avg Response Time -->
            <div class="bg-gradient-to-br from-cyan-600 to-blue-400 text-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider opacity-90 mb-2" data-translate>
                            เวลาตอบกลับเฉลี่ย
                        </p>
                        <p class="text-3xl font-bold">{{ $avgResponseTime ?? '0' }}h</p>
                    </div>
                    <div class="glass-fusion bg-opacity-20 rounded-full p-4" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Ticket Trends Chart -->
            <div class="lg:col-span-2 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>
                        แนวโน้มทิกเก็ต
                    </h2>
                </div>
                <div class="p-6">
                    <canvas id="ticketTrendsChart"></canvas>
                </div>
            </div>

            <!-- Tickets by Priority -->
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>
                        ทิกเก็ตตามลำดับความสำคัญ
                    </h2>
                </div>
                <div class="p-6">
                    <canvas id="priorityChart"></canvas>
                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>สูง</span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $highPriority ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>ปานกลาง</span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $mediumPriority ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-cyan-500 rounded-full"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>ต่ำ</span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $lowPriority ?? '0' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Recent Tickets & Top Issues -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Tickets -->
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>
                        ทิกเก็ตล่าสุด
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    รหัส
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    หัวข้อ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    ลำดับความสำคัญ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    สถานะ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    การดำเนินการ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentTickets ?? [] as $ticket)
                            <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $ticket->id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ Str::limit($ticket->subject ?? 'N/A', 30) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($ticket->priority == 'high')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" data-translate>
                                            สูง
                                        </span>
                                    @elseif($ticket->priority == 'medium')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>
                                            ปานกลาง
                                        </span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200" data-translate>
                                            ต่ำ
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($ticket->status == 'open')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>
                                            เปิด
                                        </span>
                                    @elseif($ticket->status == 'resolved')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" data-translate>
                                            แก้ไขแล้ว
                                        </span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300" data-translate>
                                            ปิด
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('admin.bot-automation.support.tickets.show', $ticket->id ?? 0) }}"
                                       class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-600 dark:text-blue-200 rounded-xl transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <span data-translate>ไม่มีทิกเก็ตล่าสุด</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Issues -->
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>
                        ปัญหายอดนิยม
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($topIssues ?? [] as $issue)
                        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $issue->category ?? 'N/A' }}
                                </h3>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs font-semibold rounded-full">
                                    {{ $issue->count ?? '0' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                {{ $issue->description ?? 'ไม่มีคำอธิบาย' }}
                            </p>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีปัญหาที่จะแสดง</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Translate Script -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    // ฟังก์ชันสำหรับแปลภาษาด้วย Google Translate API
    async function translateText(text, targetLang) {
        if (targetLang === 'th') return text; // ไม่ต้องแปลถ้าเป็นภาษาไทยอยู่แล้ว

        try {
            const response = await axios.post('/api/translate', {
                text: text,
                target: targetLang,
                source: 'th'
            });
            return response.data.translatedText;
        } catch (error) {
            console.error('Translation error:', error);
            return text; // คืนค่าข้อความเดิมถ้าแปลไม่สำเร็จ
        }
    }

    // แปลข้อความทั้งหมดที่มี data-translate attribute
    async function translatePage(targetLang) {
        const elements = document.querySelectorAll('[data-translate]');

        for (const element of elements) {
            const originalText = element.getAttribute('data-original') || element.textContent.trim();

            // เก็บข้อความต้นฉบับไว้ในครั้งแรก
            if (!element.getAttribute('data-original')) {
                element.setAttribute('data-original', originalText);
            }

            if (targetLang === 'th') {
                element.textContent = originalText;
            } else {
                const translatedText = await translateText(originalText, targetLang);
                element.textContent = translatedText;
            }
        }
    }

    // ฟังการเปลี่ยนแปลงภาษา
    document.addEventListener('alpine:initialized', () => {
        Alpine.effect(() => {
            const lang = Alpine.store('language');
            if (lang) {
                translatePage(lang);
            }
        });
    });
});

// Alpine.js global store สำหรับ language state
if (typeof Alpine !== 'undefined') {
    Alpine.store('language', 'th');
}
</script>
@endsection
