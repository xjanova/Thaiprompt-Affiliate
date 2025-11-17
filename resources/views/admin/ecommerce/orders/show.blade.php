@extends('layouts.admin-v3')

@section('title', 'รายละเอียดคำสั่งซื้อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📋 <span data-translate>คำสั่งซื้อ</span> #{{ $order->order_number }}</h1>

        <div class="flex items-center gap-4">
            {{-- Language Switcher --}}
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-600 text-white rounded-xl hover:from-orange-600 hover:to-pink-700 transition-all duration-200 shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span data-translate>ภาษา</span>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden z-50" border border-white/20 dark:border-white/10>
                    <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                    </a>
                    <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇬🇧</span> English
                    </a>
                    <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇨🇳</span> 中文
                    </a>
                    <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇯🇵</span> 日本語
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.ecommerce.orders.index') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700">
                ← <span data-translate>กลับ</span>
            </a>
        </div>
    </div>

    <!-- Order Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-translate>ข้อมูลลูกค้า</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold" data-translate>ชื่อ:</span> {!! $order->user->name ?? '<span data-translate>ไม่ระบุ</span>' !!}</p>
                <p><span class="font-semibold" data-translate>อีเมล:</span> {!! $order->user->email ?? '<span data-translate>ไม่ระบุ</span>' !!}</p>
                <p><span class="font-semibold" data-translate>เบอร์โทร:</span> {!! $order->customer_phone ?? '<span data-translate>ไม่ระบุ</span>' !!}</p>
            </div>
        </div>

        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-translate>สถานะ</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold" data-translate>สถานะคำสั่งซื้อ:</span> {{ $order->status }}</p>
                <p><span class="font-semibold" data-translate>สถานะการชำระเงิน:</span> {{ $order->payment_status }}</p>
                <p><span class="font-semibold" data-translate>วิธีชำระเงิน:</span> {!! $order->payment_method ?? '<span data-translate>ไม่ระบุ</span>' !!}</p>
            </div>
        </div>

        <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-translate>วันที่</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold" data-translate>สั่งซื้อเมื่อ:</span> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->paid_at)
                    <p><span class="font-semibold" data-translate>ชำระเมื่อ:</span> {{ $order->paid_at->format('d/m/Y H:i') }}</p>
                @endif
                @if($order->shipped_at)
                    <p><span class="font-semibold" data-translate>จัดส่งเมื่อ:</span> {{ $order->shipped_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-translate>รายการสินค้า</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase" data-translate>สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase" data-translate>ราคา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase" data-translate>จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase" data-translate>รวม</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{!! $item->product->name ?? '<span data-translate>ไม่ระบุ</span>' !!}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">SKU: {{ $item->product->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                ฿{{ number_format($item->price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                ฿{{ number_format($item->price * $item->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white" data-translate>ยอดรวม:</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">฿{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
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
