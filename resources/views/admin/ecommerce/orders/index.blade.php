@extends('layouts.admin')

@section('title', 'จัดการคำสั่งซื้อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📋 <span data-translate>จัดการคำสั่งซื้อ</span></h1>

        {{-- Language Switcher --}}
        <div class="relative inline-block" x-data="{ open: false }">
            <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-600 text-white rounded-xl hover:from-orange-600 hover:to-pink-700 transition-all duration-200 shadow-lg flex items-center gap-2">
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
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาเลขที่คำสั่งซื้อ..." data-translate-placeholder="ค้นหาเลขที่คำสั่งซื้อ..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                <option value="" data-translate>ทุกสถานะ</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }} data-translate>รอดำเนินการ</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }} data-translate>กำลังจัดเตรียม</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }} data-translate>จัดส่งแล้ว</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }} data-translate>เสร็จสิ้น</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }} data-translate>ยกเลิก</option>
            </select>
            <select name="payment_status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                <option value="" data-translate>ทุกสถานะการชำระเงิน</option>
                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }} data-translate>รอชำระเงิน</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }} data-translate>ชำระแล้ว</option>
                <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }} data-translate>ชำระไม่สำเร็จ</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                <span data-translate>ค้นหา</span>
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>เลขที่คำสั่งซื้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>ยอดรวม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>ชำระเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $order->order_number }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($order->user->name)
                                        {{ $order->user->name }}
                                    @else
                                        <span data-translate>ไม่ระบุ</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->items->sum('quantity') }} <span data-translate>รายการ</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                ฿{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'รอดำเนินการ',
                                        'processing' => 'กำลังจัดเตรียม',
                                        'shipped' => 'จัดส่งแล้ว',
                                        'completed' => 'เสร็จสิ้น',
                                        'cancelled' => 'ยกเลิก',
                                    ];
                                @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}" data-translate="{{ $statusLabels[$order->status] ?? $order->status }}">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $paymentColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                    ];
                                    $paymentLabels = [
                                        'pending' => 'รอชำระเงิน',
                                        'paid' => 'ชำระแล้ว',
                                        'failed' => 'ไม่สำเร็จ',
                                        'refunded' => 'คืนเงินแล้ว',
                                    ];
                                @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100 text-gray-800' }}" data-translate="{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}">
                                    {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium" data-translate="ดูรายละเอียด">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <span data-translate>ไม่พบคำสั่งซื้อ</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $orders->links() }}
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
