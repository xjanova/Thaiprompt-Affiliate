@extends('layouts.admin')

@section('title', 'ไปป์ไลน์การขาย')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="fixed top-20 right-4 z-50">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>ไปป์ไลน์การขาย</h1>
            <a href="{{ route('admin.bot-automation.sales.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-lg shadow-lg transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปแดชบอร์ด</span>
            </a>
        </div>

        <!-- Pipeline Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <!-- Stage 1: Lead -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-orange-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ลีด</h6>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $pipelineStats['lead'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['lead'] ?? 0, 0) }}</p>
            </div>

            <!-- Stage 2: Qualified -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-amber-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ผ่านคุณสมบัติ</h6>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $pipelineStats['qualified'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['qualified'] ?? 0, 0) }}</p>
            </div>

            <!-- Stage 3: Proposal -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-yellow-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>ข้อเสนอ</h6>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $pipelineStats['proposal'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['proposal'] ?? 0, 0) }}</p>
            </div>

            <!-- Stage 4: Negotiation -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-blue-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2" data-translate>เจรจา</h6>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $pipelineStats['negotiation'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['negotiation'] ?? 0, 0) }}</p>
            </div>

            <!-- Stage 5: Closed Won -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-green-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-green-600 dark:text-green-400 mb-2" data-translate>ปิดการขายสำเร็จ</h6>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">{{ $pipelineStats['won'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['won'] ?? 0, 0) }}</p>
            </div>

            <!-- Stage 6: Closed Lost -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-t-4 border-red-500 transform hover:scale-105 transition-transform duration-200">
                <h6 class="text-sm font-medium text-red-600 dark:text-red-400 mb-2" data-translate>ปิดการขายไม่สำเร็จ</h6>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mb-1">{{ $pipelineStats['lost'] ?? '0' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pipelineValue['lost'] ?? 0, 0) }}</p>
            </div>
        </div>

        <!-- Pipeline Visualization Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>การแสดงภาพไปป์ไลน์</h3>
            </div>
            <div class="p-6">
                <div class="chart-area">
                    <canvas id="pipelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pipeline Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>รายละเอียดไปป์ไลน์</h3>
                <div>
                    <select class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" id="filterStage">
                        <option value=""><span data-translate>ขั้นตอนทั้งหมด</span></option>
                        <option value="lead"><span data-translate>ลีด</span></option>
                        <option value="qualified"><span data-translate>ผ่านคุณสมบัติ</span></option>
                        <option value="proposal"><span data-translate>ข้อเสนอ</span></option>
                        <option value="negotiation"><span data-translate>เจรจา</span></option>
                        <option value="won"><span data-translate>ปิดการขายสำเร็จ</span></option>
                        <option value="lost"><span data-translate>ปิดการขายไม่สำเร็จ</span></option>
                    </select>
                </div>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ชื่อลีด</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>บอท</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ขั้นตอน</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>มูลค่า</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>โอกาส</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>คาดว่าจะปิด</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ติดต่อล่าสุด</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($pipelineLeads ?? [] as $lead)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $lead->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $lead->bot_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $lead->stage_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                        {{ ucfirst($lead->stage ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                    ${{ number_format($lead->value ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $lead->probability ?? '0' }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ isset($lead->expected_close) ? date('M d, Y', strtotime($lead->expected_close)) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ isset($lead->last_contact) ? date('M d, Y', strtotime($lead->last_contact)) : 'ไม่เคย' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.bot-automation.sales.leads.show', $lead->id ?? 0) }}" class="inline-flex items-center px-3 py-2 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <button onclick="updateStage({{ $lead->id }})" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                        <p class="text-lg font-medium" data-translate>ไม่มีลีดในไปป์ไลน์</p>
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
// ฟังก์ชันอัพเดทขั้นตอน
function updateStage(leadId) {
    console.log('Updating stage for lead:', leadId);
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
