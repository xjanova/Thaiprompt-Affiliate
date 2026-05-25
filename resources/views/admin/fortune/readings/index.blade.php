@extends('layouts.admin-v3')

@section('title', 'ประวัติการทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            📊 ประวัติการทำนาย
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            ดูประวัติและสถิติการทำนายทั้งหมด
        </p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['total']) }}</div>
            <div class="text-blue-100 text-sm">ทั้งหมด</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['today']) }}</div>
            <div class="text-green-100 text-sm">วันนี้</div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['deep'] ?? 0) }}</div>
            <div class="text-indigo-100 text-sm">🌟 เชิงลึก</div>
        </div>

        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['basic'] ?? 0) }}</div>
            <div class="text-cyan-100 text-sm">🔮 พื้นฐาน</div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['paid']) }}</div>
            <div class="text-purple-100 text-sm">ชำระเงินแล้ว</div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-2xl font-bold mb-1">{{ number_format($stats['free']) }}</div>
            <div class="text-orange-100 text-sm">ฟรี</div>
        </div>
    </div>

    {{-- ⚠️ Warning: บิลที่ชำระแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ --}}
    @if(($stats['stuck_paid'] ?? 0) > 0)
        <div class="mb-6 px-5 py-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="text-red-800 dark:text-red-200 font-bold text-lg">
                        มี {{ $stats['stuck_paid'] }} บิลที่ชำระเงินแล้วแต่ยังไม่มีคำทำนาย!
                    </p>
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">
                        ลูกค้าจ่ายเงินแล้วแต่ยังไม่ได้รับคำทำนาย อาจเกิดจาก: AI quota หมด, API key หมดอายุ, เครือข่ายขัดข้อง
                    </p>
                    <p class="text-red-500 dark:text-red-500 text-sm mt-1">
                        กรุณาเข้าไปกดปุ่ม "สร้างคำทำนายเชิงลึก" ในแต่ละรายการ หรือตรวจสอบ AI API keys ใน
                        <a href="{{ route('admin.fortune.settings.index') }}" class="underline font-medium">ตั้งค่าระบบดูดวง</a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Nav --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.fortune.dashboard') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
            <span>📊</span> Dashboard
        </a>
        <a href="{{ route('admin.fortune.settings.index') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
            <span>⚙️</span> ตั้งค่า
        </a>
        <a href="{{ route('admin.fortune.astrology.index') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
            <span>✨</span> โหราศาสตร์
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search by name / bill reference / platform user id --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🔍 ค้นหา (ชื่อ / รหัสบิล)
                </label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อ หรือ FTU-260425-T4022..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            {{-- Category filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    📂 หมวดคำทำนาย
                </label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="ความรัก" {{ request('category') === 'ความรัก' ? 'selected' : '' }}>💕 ความรัก</option>
                    <option value="การงาน" {{ request('category') === 'การงาน' ? 'selected' : '' }}>💼 การงาน</option>
                    <option value="การเงิน" {{ request('category') === 'การเงิน' ? 'selected' : '' }}>💰 การเงิน</option>
                    <option value="สุขภาพ" {{ request('category') === 'สุขภาพ' ? 'selected' : '' }}>🏥 สุขภาพ</option>
                    <option value="โชคลาภ" {{ request('category') === 'โชคลาภ' ? 'selected' : '' }}>🎰 โชคลาภ</option>
                    <option value="การเรียน" {{ request('category') === 'การเรียน' ? 'selected' : '' }}>📚 การเรียน</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    AI Provider
                </label>
                <select name="ai_provider" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="gemini" {{ request('ai_provider') === 'gemini' ? 'selected' : '' }}>Gemini</option>
                    <option value="groq" {{ request('ai_provider') === 'groq' ? 'selected' : '' }}>Groq</option>
                    <option value="qwen" {{ request('ai_provider') === 'qwen' ? 'selected' : '' }}>Qwen</option>
                    <option value="grok" {{ request('ai_provider') === 'grok' ? 'selected' : '' }}>Grok</option>
                    <option value="deepseek" {{ request('ai_provider') === 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                    <option value="openrouter" {{ request('ai_provider') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                    <option value="typhoon" {{ request('ai_provider') === 'typhoon' ? 'selected' : '' }}>Typhoon</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สถานะ Conversation
                </label>
                <select name="conversation_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="new" {{ request('conversation_status') === 'new' ? 'selected' : '' }}>🆕 New</option>
                    <option value="basic_done" {{ request('conversation_status') === 'basic_done' ? 'selected' : '' }}>🔮 Basic Done</option>
                    <option value="collecting_birthdate" {{ request('conversation_status') === 'collecting_birthdate' ? 'selected' : '' }}>📅 Collecting Birthdate</option>
                    <option value="collecting_questions" {{ request('conversation_status') === 'collecting_questions' ? 'selected' : '' }}>❓ Collecting Questions</option>
                    <option value="pending_payment" {{ request('conversation_status') === 'pending_payment' ? 'selected' : '' }}>💳 Pending Payment</option>
                    <option value="paid" {{ request('conversation_status') === 'paid' ? 'selected' : '' }}>✅ Paid</option>
                    <option value="completed" {{ request('conversation_status') === 'completed' ? 'selected' : '' }}>🏁 Completed</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สถานะชำระเงิน
                </label>
                <select name="is_paid" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>💰 ชำระเงินแล้ว</option>
                    <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>🆓 ฟรี</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทคำทำนาย
                </label>
                <select name="reading_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="basic" {{ request('reading_type') === 'basic' ? 'selected' : '' }}>🔮 พื้นฐาน</option>
                    <option value="deep" {{ request('reading_type') === 'deep' ? 'selected' : '' }}>🌟 เชิงลึก</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่เริ่มต้น
                </label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่สิ้นสุด
                </label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div class="md:col-span-4 flex flex-wrap gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    🔍 กรองข้อมูล
                </button>
                <a href="{{ route('admin.fortune.readings.index') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้างตัวกรอง
                </a>
                <a href="{{ route('admin.fortune.readings.export', request()->all()) }}"
                   class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    📥 Export CSV
                </a>
            </div>
        </form>
    </div>

    {{-- Readings Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้ / รหัสบิล
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            หมวด
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            AI
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($readings as $reading)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $reading->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $reading->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        {{ mb_substr($reading->facebook_user_name ?? '?', 0, 1) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($reading->facebook_user_id, 15) }}</div>
                                        @if($reading->bill_reference)
                                            {{-- 🧾 รหัสบิล — คลิก copy ได้ --}}
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $reading->bill_reference }}'); this.innerText='✅ คัดลอกแล้ว'; setTimeout(() => this.innerText='🧾 {{ $reading->bill_reference }}', 1500);"
                                                    class="mt-0.5 text-xs font-mono text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 truncate cursor-pointer text-left"
                                                    title="คลิกเพื่อคัดลอกรหัสบิล">
                                                🧾 {{ $reading->bill_reference }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @php $cats = $reading->categories ?? []; @endphp
                                @if(!empty($cats))
                                    @foreach(array_slice($cats, 0, 2) as $cat)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 mr-0.5">
                                            {{ $cat }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @if($reading->reading_type === 'deep')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        🌟 เชิงลึก
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                        🔮 พื้นฐาน
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ strtoupper($reading->ai_provider) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @if($reading->is_paid)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        💰 ฿{{ number_format($reading->amount_paid, 2) }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        🆓 ฟรี
                                    </span>
                                @endif
                                @php
                                    // 🏷️ (2026-05-25) Cancellation badge — ก่อน badge อื่นๆ
                                    $readingCancellationLabel = $reading->getCancellationReasonLabelOrNull();
                                @endphp
                                @if($readingCancellationLabel)
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300"
                                              title="reason: {{ data_get($reading->conversation_state, 'cancellation_reason') }}">
                                            ❌ {{ $readingCancellationLabel }}
                                        </span>
                                    </div>
                                {{-- 🛟 (2026-05-14) Pay-First incomplete — ลูกค้าจ่ายแล้วแต่ไม่กรอกข้อมูล --}}
                                @elseif($reading->is_paid && $reading->reading_type === 'deep' && empty($reading->birth_date) && empty($reading->deep_response))
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300" title="ลูกค้าจ่ายแล้วแต่ยังไม่กรอกวันเกิด">
                                            🛟 รอวันเกิด
                                        </span>
                                    </div>
                                @elseif($reading->is_paid && empty($reading->deep_response) && $reading->reading_type === 'deep')
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300" title="จ่ายแล้ว — ยังไม่ส่งคำทำนาย">
                                            ⏳ ยังไม่ส่งคำทำนาย
                                        </span>
                                    </div>
                                @elseif($reading->conversation_status && $reading->conversation_status !== 'completed')
                                    {{-- Conversation status badge (เคสอื่นๆ) --}}
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                            {{ $reading->conversation_status }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.fortune.readings.show', $reading) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition">
                                    👁️ ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">🔮</div>
                                ไม่พบประวัติการทำนาย
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($readings->hasPages())
        <div class="mt-6">
            {{ $readings->links() }}
        </div>
    @endif
</div>
@endsection
