{{-- Celtic Cross Tarot Mode Settings --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
            🔮 Celtic Cross Tarot Mode
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross — ค่าครู 99 บาท / 3 คำถาม / 1 ชั่วโมง window
        </p>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Readings ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_readings']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">ชำระแล้ว</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['paid_readings']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">เสร็จวันนี้</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['completed_today'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">รายได้รวม</p>
            <p class="text-xl font-bold text-amber-600 mt-1">฿{{ number_format($stats['total_revenue'], 0) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">คำถามรวม</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['total_questions']) }}</p>
        </div>
    </div>

    <form action="{{ route('admin.fortune.celtic-cross.settings.update') }}" method="POST" class="mb-8">
        @csrf
        @method('PUT')

        {{-- Toggles --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                ⚙️ สถานะระบบ
            </h2>

            <label class="flex items-start gap-3 cursor-pointer p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border-2 border-purple-300 dark:border-purple-700 mb-3">
                <input type="hidden" name="enable_celtic_cross" value="0">
                <input type="checkbox" name="enable_celtic_cross" value="1"
                       class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500"
                       {{ $settings->enable_celtic_cross ? 'checked' : '' }}>
                <div class="flex-1">
                    <span class="font-semibold text-gray-900 dark:text-white">🔮 เปิดบริการ Celtic Cross Tarot Mode</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        เมื่อเปิด — ลูกค้าจะเลือกเมนูนี้ได้ตอนทำนายเสร็จพื้นฐาน หรือพิมพ์ "celtic cross" เพื่อเริ่ม
                    </p>
                </div>
            </label>

            <label class="flex items-start gap-3 cursor-pointer p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <input type="hidden" name="celtic_cross_proactive_enabled" value="0">
                <input type="checkbox" name="celtic_cross_proactive_enabled" value="1"
                       class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500"
                       {{ $settings->celtic_cross_proactive_enabled ? 'checked' : '' }}>
                <div class="flex-1">
                    <span class="font-semibold text-gray-900 dark:text-white">🤖 AI เชิญชวนเองเมื่อลูกค้าทุกข์มาก</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        AI จะแนะนำ Celtic Cross เมื่อตรวจพบสัญญาณความทุกข์ในแชทปกติ (throttled — เสนอครั้งเดียวต่อ session)
                    </p>
                </div>
            </label>
        </div>

        {{-- Pricing --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💰 ราคา & เงื่อนไข</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ค่าครู (บาท)
                    </label>
                    <input type="number" name="celtic_cross_price" step="0.01"
                           value="{{ $settings->celtic_cross_price ?? 99 }}"
                           min="1" max="9999"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนคำถาม/บิล <span class="text-xs text-purple-600 dark:text-purple-400">(0 = ไม่จำกัด)</span>
                    </label>
                    <input type="number" name="celtic_cross_max_questions"
                           value="{{ $settings->celtic_cross_max_questions ?? 5 }}"
                           min="0" max="50"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">📊 บังคับ enforcement (2026-05-03) — ครบโควต้าจะจบ session ให้คำตอบสุดท้าย</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เวลาถามต่อ (นาที)
                    </label>
                    <input type="number" name="celtic_cross_qa_window_minutes"
                           value="{{ $settings->celtic_cross_qa_window_minutes ?? 30 }}"
                           min="5" max="1440"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">นับจากคำทำนายแรก — เกินเวลา session จบอัตโนมัติ (default 30 นาที)</p>
                </div>
            </div>
        </div>

        {{-- AI Prompts --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🤖 AI Prompts (ขั้นสูง)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                เว้นว่างเพื่อใช้ default — แก้ที่นี่ถ้าอยากปรับโทน/สไตล์ AI<br>
                ตัวแปรที่ใช้ได้: <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1">{question}</code>
                <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1">{cards}</code>
                <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1">{brand_name}</code>
                <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1">{sequence}</code>
                <br><span class="text-xs text-amber-600 dark:text-amber-400">⚠️ แม่หมอจันทรา ไม่ใช้วันเกิด — ใช้พลังจักรวาล + จิตเจ้าชะตาเท่านั้น</span>
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    📝 Main Prompt — Q1 (full storytelling)
                </label>
                <textarea name="celtic_cross_main_prompt" rows="10"
                          placeholder="เว้นว่างใช้ default — แต่งคำสั่ง AI ที่นี่ ใช้ตัวแปร {question}, {cards}, {brand_name}"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-purple-500">{{ $settings->celtic_cross_main_prompt }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    📝 Followup Prompt — Q2/Q3 (no card explain)
                </label>
                <textarea name="celtic_cross_followup_prompt" rows="8"
                          placeholder="เว้นว่างใช้ default"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-purple-500">{{ $settings->celtic_cross_followup_prompt }}</textarea>
            </div>
        </div>

        <div class="flex justify-end mb-8">
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl shadow-lg font-semibold transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- Recent Readings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📜 Celtic Cross Readings</h2>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-3 mb-4 rounded-r-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-3 mb-4 rounded-r-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- 🔍 (2026-05-03) Filter form --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-5 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="🔍 ค้นชื่อ / Bill / FB ID / #ID"
                   class="md:col-span-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                <option value="">— ทุกสถานะ —</option>
                <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                <option value="unpaid" {{ ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' }}>ยังไม่จ่าย</option>
                <option value="stuck" {{ ($filters['status'] ?? '') === 'stuck' ? 'selected' : '' }}>ค้าง (paid + ไม่ครบ 10)</option>
                <option value="celtic_pending_payment" {{ ($filters['status'] ?? '') === 'celtic_pending_payment' ? 'selected' : '' }}>รอชำระ</option>
                <option value="celtic_picking" {{ ($filters['status'] ?? '') === 'celtic_picking' ? 'selected' : '' }}>กำลังเลือกไพ่</option>
                <option value="celtic_awaiting_question" {{ ($filters['status'] ?? '') === 'celtic_awaiting_question' ? 'selected' : '' }}>รอคำถาม</option>
                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>จบแล้ว</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-semibold">
                🔍 ค้นหา
            </button>
        </form>

        @if($recentReadings->isEmpty())
            <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มี Celtic Cross reading</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs uppercase">Bill</th>
                            <th class="px-3 py-2 text-left text-xs uppercase">ลูกค้า</th>
                            <th class="px-3 py-2 text-left text-xs uppercase">สถานะ</th>
                            <th class="px-3 py-2 text-left text-xs uppercase">ค่าครู</th>
                            <th class="px-3 py-2 text-left text-xs uppercase">Q ใช้</th>
                            <th class="px-3 py-2 text-left text-xs uppercase">เวลาเหลือ</th>
                            <th class="px-3 py-2 text-right text-xs uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentReadings as $reading)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-2 font-mono text-xs">{{ $reading->bill_reference ?? '#'.$reading->id }}</td>
                                <td class="px-3 py-2">{{ $reading->facebook_user_name ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 rounded text-xs
                                        {{ $reading->conversation_status === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300' }}">
                                        {{ $reading->conversation_status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    @if($reading->is_paid)
                                        <span class="text-green-600">฿{{ number_format($reading->amount_paid, 0) }} ✓</span>
                                    @else
                                        <span class="text-gray-400">รอ</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $reading->celtic_questions_used }} คำถาม</td>
                                <td class="px-3 py-2 text-xs text-gray-500">
                                    @php
                                        // 🆕 (2026-05-22) อ่าน Pro Session state ก่อน (รองรับ admin extend)
                                        // fallback: สูตรเก่า celtic_first_answered_at + setting (legacy 3Q flow)
                                        $_proActive = (bool) $reading->getConversationState('pro_session_active', false);
                                        $_proStartedAt = $reading->getConversationState('pro_session_started_at');
                                        $_proWindowMin = (int) $reading->getConversationState('pro_session_window_minutes', $settings->celtic_cross_qa_window_minutes ?? 30);
                                        $_minRemaining = null;
                                        $_isExtended = false;

                                        if ($_proActive && ! empty($_proStartedAt)) {
                                            try {
                                                $_elapsed = (int) \Carbon\Carbon::parse($_proStartedAt)->diffInMinutes(now(), true);
                                                $_minRemaining = max(0, $_proWindowMin - $_elapsed);
                                                $_isExtended = $_proWindowMin > ($settings->celtic_cross_qa_window_minutes ?? 30);
                                            } catch (\Throwable $e) {
                                                $_minRemaining = null;
                                            }
                                        }

                                        if ($_minRemaining === null && $reading->celtic_first_answered_at) {
                                            $_deadline = $reading->celtic_first_answered_at->copy()->addMinutes($settings->celtic_cross_qa_window_minutes ?? 30);
                                            $_minRemaining = max(0, (int) ceil(now()->diffInSeconds($_deadline, false) / 60));
                                        }
                                    @endphp
                                    @if($_minRemaining === null)
                                        <span>—</span>
                                    @elseif($_minRemaining > 0)
                                        <span class="text-green-600">{{ $_minRemaining }} นาที</span>
                                        @if($_isExtended)
                                            <span class="text-cyan-600 dark:text-cyan-400 ml-1" title="แอดมินขยายเวลาแล้ว (window {{ $_proWindowMin }}m)">⏰+</span>
                                        @endif
                                    @else
                                        <span class="text-red-600">หมดเวลา</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('admin.fortune.celtic-cross.show', $reading) }}"
                                       class="text-purple-600 hover:underline text-xs">ดู</a>
                                    @if($reading->is_paid && $reading->celticQuestions_count == 0 && $reading->getCelticPickedCount() < 10)
                                        {{-- 🔄 Quick reset for stuck readings --}}
                                        <form action="{{ route('admin.fortune.celtic-cross.reset', $reading) }}" method="POST"
                                              onsubmit="return confirm('Reset reading #{{ $reading->id }}?');"
                                              class="inline ml-2">
                                            @csrf
                                            <button type="submit" class="text-amber-600 hover:underline text-xs">🔄 reset</button>
                                        </form>
                                    @elseif(! $reading->is_paid && (int) ($reading->celtic_questions_used ?? 0) === 0)
                                        {{-- 🗑️ (2026-05-04) Quick cancel for pending-payment bills (ขัดกัน) --}}
                                        <form action="{{ route('admin.fortune.celtic-cross.cancel', $reading) }}" method="POST"
                                              onsubmit="return confirm('ยกเลิกบิล {{ $reading->bill_reference ?? '#' . $reading->id }}? (ปลอดภัยเพราะยังไม่จ่าย)');"
                                              class="inline ml-2">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:underline text-xs">🗑️ ลบ</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $recentReadings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
