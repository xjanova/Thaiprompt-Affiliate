@extends('layouts.admin')

@section('title', 'รายงานยอดขาย')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📈 <span data-translate>รายงานยอดขาย</span></h1>

        {{-- Language Switcher --}}
        <div class="relative inline-block" x-data="{ open: false }">
            <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-200 shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span data-translate>ภาษา</span>
            </button>

            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                </a>
                <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <span class="mr-2">🇬🇧</span> English
                </a>
                <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <span class="mr-2">🇨🇳</span> 中文
                </a>
                <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <span class="mr-2">🇯🇵</span> 日本語
                </a>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate>จากวันที่</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate>ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                    <span data-translate>ค้นหา</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Sales Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ยอดขายรวม</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                ฿{{ number_format($salesReport->sum('revenue') ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>จำนวนคำสั่งซื้อ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($salesReport->sum('orders') ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ยอดเฉลี่ยต่อคำสั่งซื้อ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                ฿{{ $salesReport->sum('orders') > 0 ? number_format($salesReport->sum('revenue') / $salesReport->sum('orders')) : 0 }}
            </p>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4" data-translate>สินค้าขายดี</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>อันดับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>ยอดขาย</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topProducts ?? [] as $index => $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $product->sku }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $product->total_sales ?? 0 }} <span data-translate>ชิ้น</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400" data-translate>
                                ไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ฟังก์ชันแปลภาษาด้วย Google Translate API
 *
 * @param {string} targetLang รหัสภาษาเป้าหมาย (en, zh, ja)
 */
async function translatePage(targetLang) {
    // ถ้าเลือกภาษาไทย ให้โหลดหน้าใหม่เพื่อแสดงข้อความต้นฉบับ
    if (targetLang === 'th') {
        location.reload();
        return;
    }

    // ดึง elements ที่มี data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');

    try {
        // สร้าง array ของข้อความที่ต้องการแปล
        const textsToTranslate = Array.from(elements).map(el => {
            // เก็บข้อความต้นฉบับไว้ใน dataset
            if (!el.dataset.originalText) {
                el.dataset.originalText = el.textContent.trim();
            }
            return el.dataset.originalText;
        });

        // เรียก Google Translate API
        const apiKey = '{{ config("services.google_translate.key", "") }}';

        if (!apiKey) {
            console.warn('Google Translate API key not configured');
            return;
        }

        const response = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: textsToTranslate,
                source: 'th',
                target: targetLang,
                format: 'text'
            })
        });

        const data = await response.json();

        // อัพเดทข้อความที่แปลแล้ว
        if (data.data && data.data.translations) {
            data.data.translations.forEach((translation, index) => {
                if (elements[index]) {
                    elements[index].textContent = translation.translatedText;
                }
            });
        }

    } catch (error) {
        console.error('Translation error:', error);
    }
}

// ติดตั้ง watcher สำหรับ Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        }
    });
});
</script>
@endpush
@endsection
