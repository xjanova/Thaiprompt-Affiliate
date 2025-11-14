@extends('layouts.admin')

@section('title', 'จัดการรีวิวสินค้า')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⭐ <span data-translate>จัดการรีวิวสินค้า</span></h1>

        {{-- Language Switcher --}}
        <div class="relative inline-block" x-data="{ open: false }">
            <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-600 text-white rounded-xl hover:from-yellow-600 hover:to-orange-700 transition-all duration-200 shadow-lg flex items-center gap-2">
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

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหารีวิว..." data-translate-placeholder class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            <select name="rating" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                <option value=""><span data-translate>ทุกคะแนน</span></option>
                <option value="5" {{ request('rating') == 5 ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5)</option>
                <option value="4" {{ request('rating') == 4 ? 'selected' : '' }}>⭐⭐⭐⭐ (4)</option>
                <option value="3" {{ request('rating') == 3 ? 'selected' : '' }}>⭐⭐⭐ (3)</option>
                <option value="2" {{ request('rating') == 2 ? 'selected' : '' }}>⭐⭐ (2)</option>
                <option value="1" {{ request('rating') == 1 ? 'selected' : '' }}>⭐ (1)</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                <span data-translate>ค้นหา</span>
            </button>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>สินค้า</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>ลูกค้า</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>คะแนน</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>ความคิดเห็น</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>วันที่</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>การกระทำ</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->product->name ?? '<span data-translate>ไม่ระบุ</span>' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $review->product->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $review->user->name ?? '<span data-translate>ไม่ระบุ</span>' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $review->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="text-yellow-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            ⭐
                                        @else
                                            ☆
                                        @endif
                                    @endfor
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ Str::limit($review->comment ?? '', 50) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $review->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <form action="{{ route('admin.ecommerce.reviews.status.update', $review) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="is_approved" value="{{ $review->is_approved ? 0 : 1 }}">
                                    <button type="submit" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 font-medium">
                                        <span data-translate>{{ $review->is_approved ? 'ซ่อน' : 'อนุมัติ' }}</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.ecommerce.reviews.delete', $review) }}" method="POST" class="inline" onsubmit="return confirm(this.dataset.confirmMessage)" data-confirm-message="คุณแน่ใจหรือไม่?" data-translate>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 font-medium">
                                        <span data-translate>ลบ</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <span data-translate>ไม่พบรีวิว</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $reviews->links() }}
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

        // แปล placeholders
        const placeholderElements = document.querySelectorAll('[data-translate-placeholder]');
        for (const el of placeholderElements) {
            if (!el.dataset.originalPlaceholder) {
                el.dataset.originalPlaceholder = el.placeholder;
            }

            const placeholderResponse = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    q: [el.dataset.originalPlaceholder],
                    source: 'th',
                    target: targetLang,
                    format: 'text'
                })
            });

            const placeholderData = await placeholderResponse.json();
            if (placeholderData.data && placeholderData.data.translations[0]) {
                el.placeholder = placeholderData.data.translations[0].translatedText;
            }
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
