@extends('layouts.admin-v3')

@section('title', 'รายงานการขาย')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="fixed top-20 right-4 z-50">
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

    <div class="container mx-auto px-4 py-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>รายงานการขาย</h1>
            <div class="flex flex-wrap gap-3">
                <button onclick="exportReport('excel')" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span data-translate>ส่งออก Excel</span>
                </button>
                <button onclick="exportReport('pdf')" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span data-translate>ส่งออก PDF</span>
                </button>
                <a href="{{ route('admin.bot-automation.sales.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg mb-6" border border-white/20 dark:border-white/10>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ตัวกรองรายงาน</h3>
            </div>
            <div class="p-6">
                <form>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>วันที่เริ่มต้น</label>
                            <input type="date" id="dateFrom" name="date_from" class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition">
                        </div>
                        <div>
                            <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>วันที่สิ้นสุด</label>
                            <input type="date" id="dateTo" name="date_to" class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition">
                        </div>
                        <div>
                            <label for="bot" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>บอท</label>
                            <select id="bot" name="bot_id" class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition">
                                <option value=""><span data-translate>บอททั้งหมด</span></option>
                                @foreach($bots ?? [] as $bot)
                                <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white rounded-xl shadow-lg transition-all duration-200 font-semibold">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <span data-translate>สร้างรายงาน</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Revenue -->
            <div class="bg-gradient-to-br from-orange-500 to-amber-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-orange-100 text-sm font-medium uppercase mb-2" data-translate>รายได้ทั้งหมด</p>
                        <p class="text-3xl font-bold">${{ number_format($reportData['total_revenue'] ?? 0, 2) }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Closed Deals -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-green-100 text-sm font-medium uppercase mb-2" data-translate>ดีลที่ปิด</p>
                        <p class="text-3xl font-bold">{{ $reportData['closed_deals'] ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Avg Deal Size -->
            <div class="bg-gradient-to-br from-amber-500 to-yellow-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-amber-100 text-sm font-medium uppercase mb-2" data-translate>ขนาดดีลเฉลี่ย</p>
                        <p class="text-3xl font-bold">${{ number_format($reportData['avg_deal_size'] ?? 0, 2) }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Win Rate -->
            <div class="bg-gradient-to-br from-yellow-600 to-orange-400 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-yellow-100 text-sm font-medium uppercase mb-2" data-translate>อัตราชนะ</p>
                        <p class="text-3xl font-bold">{{ number_format($reportData['win_rate'] ?? 0, 1) }}%</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Trend & Top Bots -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Revenue Trend Chart -->
            <div class="lg:col-span-2 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>แนวโน้มรายได้</h3>
                </div>
                <div class="p-6">
                    <div class="chart-area">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Performing Bots -->
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>บอทที่มีประสิทธิภาพสูงสุด</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($topBots ?? [] as $bot)
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl">
                            <div class="flex-1">
                                <h6 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $bot->name }}</h6>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400"><span data-translate>{{ $bot->deals_count ?? '0' }} ดีลปิด</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">${{ number_format($bot->revenue ?? 0, 0) }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีข้อมูล</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales by Bot Table -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>การขายตามบอท</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ชื่อบอท</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ลีดทั้งหมด</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>การแปลง</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>อัตราการแปลง</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>รายได้ทั้งหมด</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ขนาดดีลเฉลี่ย</th>
                            </tr>
                        </thead>
                        <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($salesByBot ?? [] as $bot)
                            <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $bot->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $bot->total_leads ?? '0' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $bot->conversions ?? '0' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $bot->conversion_rate >= 30 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($bot->conversion_rate >= 15 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200') }}">
                                        {{ number_format($bot->conversion_rate ?? 0, 1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                    ${{ number_format($bot->total_revenue ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    ${{ number_format($bot->avg_deal_size ?? 0, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-lg font-medium" data-translate>ไม่มีข้อมูล</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// ฟังก์ชันส่งออกรายงาน
function exportReport(format) {
    alert('กำลังส่งออกรายงานเป็น ' + format.toUpperCase() + '... ฟีเจอร์นี้อยู่ระหว่างการพัฒนา');
}

// เพิ่ม Google Translate Script
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        includedLanguages: 'th,en,zh-CN,ja',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
}

// โหลด Google Translate API
(function() {
    var gtScript = document.createElement('script');
    gtScript.type = 'text/javascript';
    gtScript.async = true;
    gtScript.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.body.appendChild(gtScript);
})();

// ฟังก์ชันแปลภาษา
function translatePage(targetLang) {
    const elements = document.querySelectorAll('[data-translate]');

    elements.forEach(element => {
        const text = element.textContent.trim();
        if (text && targetLang !== 'th') {
            fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=th&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data[0] && data[0][0]) {
                        element.textContent = data[0][0][0];
                    }
                })
                .catch(error => console.error('Translation error:', error));
        }
    });
}

// ฟังก์ชัน Alpine.js สำหรับเปลี่ยนภาษา
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value === 'en' ? 'en' : value === 'zh' ? 'zh-CN' : 'ja');
        } else {
            location.reload();
        }
    });
});
</script>
@endpush
@endsection
