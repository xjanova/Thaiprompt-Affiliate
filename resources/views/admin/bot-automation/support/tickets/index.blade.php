@extends('layouts.admin')

@section('title', 'ทิกเก็ตซัพพอร์ต')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="absolute top-0 right-0 z-10 mt-4 mr-4">
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

    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>
                ทิกเก็ตซัพพอร์ต
            </h1>
            <a href="{{ route('admin.bot-automation.support.index') }}"
               class="flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปแดชบอร์ด</span>
            </a>
        </div>

        <!-- Ticket Management Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-blue-600 dark:text-cyan-400" data-translate>
                    จัดการทิกเก็ต
                </h2>
            </div>

            <div class="p-6">
                <!-- Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <!-- Search -->
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ค้นหาทิกเก็ต
                        </label>
                        <input type="text"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                               placeholder="ค้นหา..."
                               id="searchTickets"
                               data-translate-placeholder="ค้นหา...">
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            สถานะ
                        </label>
                        <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                id="filterStatus">
                            <option value="" data-translate>ทุกสถานะ</option>
                            <option value="open" data-translate>เปิด</option>
                            <option value="in_progress" data-translate>กำลังดำเนินการ</option>
                            <option value="pending" data-translate>รอดำเนินการ</option>
                            <option value="resolved" data-translate>แก้ไขแล้ว</option>
                            <option value="closed" data-translate>ปิด</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ลำดับความสำคัญ
                        </label>
                        <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                id="filterPriority">
                            <option value="" data-translate>ทุกระดับ</option>
                            <option value="high" data-translate>สูง</option>
                            <option value="medium" data-translate>ปานกลาง</option>
                            <option value="low" data-translate>ต่ำ</option>
                        </select>
                    </div>

                    <!-- Bot Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            บอท
                        </label>
                        <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                id="filterBot">
                            <option value="" data-translate>ทุกบอท</option>
                            @foreach($bots ?? [] as $bot)
                            <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            หมวดหมู่
                        </label>
                        <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-cyan-500 focus:border-transparent transition"
                                id="filterCategory">
                            <option value="" data-translate>ทุกหมวดหมู่</option>
                            <option value="technical" data-translate>ปัญหาทางเทคนิค</option>
                            <option value="billing" data-translate>การเรียกเก็บเงิน</option>
                            <option value="feature" data-translate>คำขอฟีเจอร์</option>
                            <option value="bug" data-translate>รายงานบั๊ก</option>
                            <option value="other" data-translate>อื่นๆ</option>
                        </select>
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="ticketsTable">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    รหัส
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    หัวข้อ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    ผู้ใช้
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    บอท
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    หมวดหมู่
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    ลำดับความสำคัญ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    สถานะ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    สร้างเมื่อ
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>
                                    การดำเนินการ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($tickets ?? [] as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    #{{ $ticket->id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ Str::limit($ticket->subject ?? 'N/A', 40) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $ticket->user_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $ticket->bot_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ ucfirst($ticket->category ?? 'N/A') }}
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
                                    @elseif($ticket->status == 'in_progress')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" data-translate>
                                            กำลังดำเนินการ
                                        </span>
                                    @elseif($ticket->status == 'resolved')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" data-translate>
                                            แก้ไขแล้ว
                                        </span>
                                    @elseif($ticket->status == 'closed')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300" data-translate>
                                            ปิด
                                        </span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                            {{ ucfirst($ticket->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ isset($ticket->created_at) ? $ticket->created_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                    <a href="{{ route('admin.bot-automation.support.tickets.show', $ticket->id ?? 0) }}"
                                       class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-600 dark:text-blue-200 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if($ticket->status != 'closed')
                                    <button onclick="updateStatus({{ $ticket->id }}, 'resolved')"
                                            class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-600 dark:text-green-200 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span data-translate>ไม่พบทิกเก็ต</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($tickets) && method_exists($tickets, 'links'))
                <div class="mt-6">
                    {{ $tickets->links() }}
                </div>
                @endif
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

        // แปล placeholders
        const placeholders = document.querySelectorAll('[data-translate-placeholder]');
        for (const element of placeholders) {
            const originalPlaceholder = element.getAttribute('data-original-placeholder') || element.getAttribute('placeholder');

            if (!element.getAttribute('data-original-placeholder')) {
                element.setAttribute('data-original-placeholder', originalPlaceholder);
            }

            if (targetLang === 'th') {
                element.setAttribute('placeholder', originalPlaceholder);
            } else {
                const translatedPlaceholder = await translateText(originalPlaceholder, targetLang);
                element.setAttribute('placeholder', translatedPlaceholder);
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

// ฟังก์ชันอัพเดทสถานะทิกเก็ต
async function updateStatus(ticketId, status) {
    // แสดง confirmation dialog
    if (!confirm(`อัพเดทสถานะทิกเก็ตเป็น "${status}"?`)) {
        return;
    }

    try {
        // ส่ง AJAX request เพื่ออัพเดทสถานะ
        const response = await fetch(`/admin/bot-automation/support/tickets/${ticketId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                status: status
            })
        });

        const result = await response.json();

        if (response.ok && result.success !== false) {
            // แสดงข้อความสำเร็จ
            showNotification('success', 'อัพเดทสถานะทิกเก็ตสำเร็จ');

            // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // แสดงข้อความผิดพลาด
            showNotification('error', result.message || 'เกิดข้อผิดพลาดในการอัพเดทสถานะ');
        }
    } catch (error) {
        console.error('Error updating ticket status:', error);
        showNotification('error', 'เกิดข้อผิดพลาด: ' + error.message);
    }
}

// ฟังก์ชันแสดง notification
function showNotification(type, message) {
    // สร้าง notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
        type === 'success'
            ? 'bg-green-500 text-white'
            : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success'
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;

    // เพิ่ม notification ลงใน body
    document.body.appendChild(notification);

    // ลบ notification หลังจาก 5 วินาที
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}
</script>
@endsection
