@extends('layouts.admin-v3')

@section('title', 'แก้ไขการทำนาย #' . $reading->id)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.fortune.readings.show', $reading) }}"
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับไปดูรายละเอียด
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            ✏️ แก้ไขการทำนาย #{{ $reading->id }}
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            ลูกค้า: <span class="font-medium">{{ $reading->facebook_user_name ?? 'ไม่ระบุ' }}</span>
            • บิล: <span class="font-mono text-xs">{{ $reading->bill_reference ?? '-' }}</span>
        </p>
    </div>

    {{-- Flash Messages --}}
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fortune.readings.update', $reading) }}"
          method="POST"
          x-data="{ submitting: false }"
          @submit="if(!confirm('ยืนยันการแก้ไขการทำนายนี้?')) { $event.preventDefault(); return; } submitting = true;">
        @csrf
        @method('PUT')

        {{-- 🏷️ (2026-05-25) Cancellation Banner — โชว์ที่ด้านบนถ้าบิลถูกยกเลิก --}}
        @php
            $cancellationLabel = $reading->getCancellationReasonLabelOrNull();
            $cancellationReason = data_get($reading->conversation_state, 'cancellation_reason');
            $cancelledAt = data_get($reading->conversation_state, 'cancelled_at');
        @endphp
        @if($cancellationLabel)
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-400 rounded-xl shadow-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <div class="text-3xl">❌</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-red-900 dark:text-red-200">
                            {{ $cancellationLabel }}
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                            <span class="font-medium">เหตุผล:</span>
                            <code class="text-xs bg-red-100 dark:bg-red-900/50 px-2 py-0.5 rounded">{{ $cancellationReason }}</code>
                            @if($cancelledAt)
                                · <span class="font-medium">เวลายกเลิก:</span> {{ \Carbon\Carbon::parse($cancelledAt)->format('Y-m-d H:i') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bill Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💳 สถานะบิล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Conversation Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        สถานะ Conversation
                    </label>
                    <select name="conversation_status"
                            class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach([
                            'new' => '🆕 ใหม่',
                            'awaiting_confirmation' => '⏳ รอยืนยัน',
                            'basic_done' => '🔮 Basic เสร็จ',
                            'collecting_birthdate' => '📅 รับวันเกิด',
                            'collecting_questions' => '❓ รับคำถาม',
                            'collecting_tarot' => '🃏 รับไพ่',
                            'pending_payment' => '💳 รอชำระ',
                            'paid' => '✅ ชำระแล้ว',
                            'completed' => '🏁 เสร็จสิ้น',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('conversation_status', $reading->conversation_status) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Is Paid --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชำระเงินแล้ว?
                    </label>
                    <select name="is_paid"
                            class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="0" @selected(old('is_paid', $reading->is_paid) == false)>❌ ยังไม่ชำระ</option>
                        <option value="1" @selected(old('is_paid', $reading->is_paid) == true)>✅ ชำระแล้ว</option>
                    </select>
                </div>

                {{-- Amount Paid --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        จำนวนเงิน (฿)
                    </label>
                    <input type="number"
                           name="amount_paid"
                           step="0.01"
                           min="0"
                           value="{{ old('amount_paid', $reading->amount_paid) }}"
                           class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                {{-- Paid At --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        วันที่ชำระ
                    </label>
                    <input type="datetime-local"
                           name="paid_at"
                           value="{{ old('paid_at', $reading->paid_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ถ้าเลือก "ชำระแล้ว" แต่ปล่อยว่าง → ระบบใช้เวลาปัจจุบัน
                    </p>
                </div>
            </div>
        </div>

        {{-- 🌙 (2026-05-14) Customer Data — admin แก้แทนลูกค้าได้ทุก field --}}
        <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-amber-900 dark:text-amber-200 mb-1">📋 ข้อมูลลูกค้า (Admin Edit)</h3>
            <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">
                💡 ใช้กรณีลูกค้ากรอกผิด/ค้าง — admin แก้แทนได้ทั้ง วันเกิด คำถาม และจับไพ่
            </p>

            <div class="space-y-4">
                {{-- Birth Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        📅 วันเกิด (ค.ศ.)
                    </label>
                    <input type="date"
                           name="birth_date"
                           value="{{ old('birth_date', $reading->birth_date?->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ปัจจุบัน: {{ $reading->birth_date ? $reading->birth_date->format('d M Y (ค.ศ.)') : '❌ ยังไม่กรอก' }}
                    </p>
                </div>

                {{-- Questions --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ❓ คำถาม (1 บรรทัด/1 คำถาม)
                    </label>
                    @php
                        $currentQuestions = is_array($reading->questions) ? $reading->questions : [];
                        $questionsText = implode("\n", $currentQuestions);
                    @endphp
                    <textarea name="questions_input"
                              rows="5"
                              placeholder="ดวงความรักปีนี้จะเป็นยังไง&#10;งานจะมั่นคงไหม&#10;การเงินช่วงนี้"
                              class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">{{ old('questions_input', $questionsText) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ปัจจุบัน: {{ count($currentQuestions) }} คำถาม
                    </p>
                </div>

                {{-- Tarot Cards --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        🃏 ไพ่ที่จับ
                    </label>
                    @php
                        $tarotCards = $reading->getCollectedTarotCards();
                    @endphp
                    @if(count($tarotCards) > 0)
                        <div class="space-y-1 mb-2">
                            @foreach($tarotCards as $idx => $card)
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    🃏 #{{ $idx + 1 }} {{ $card['card_name_th'] ?? '?' }} ({{ $card['card_name_en'] ?? '?' }})
                                    @if(! empty($card['is_reversed'])) <span class="text-orange-600">(กลับหัว)</span> @endif
                                </p>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-red-600 dark:text-red-400 mb-2">❌ ยังไม่ได้จับไพ่</p>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   name="pick_tarot_random"
                                   value="1"
                                   class="rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                🎲 จับไพ่ random ให้ตอน save (admin manual pick)
                            </span>
                        </label>
                    @endif
                </div>
            </div>

            @if($reading->is_paid && empty($reading->deep_response))
                <div class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        💡 <strong>หลังบันทึกข้อมูลครบ</strong> (วันเกิด + คำถาม + ไพ่)
                        → กลับไปหน้า show แล้วกดปุ่ม <strong>"🔄 สร้างคำทำนายเชิงลึก"</strong> เพื่อ trigger AI
                    </p>
                </div>
            @endif
        </div>

        {{-- Predictions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔮 คำทำนาย</h3>

            <div class="space-y-4">
                {{-- Deep Response (the "money prediction") --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        🌟 คำทำนายเชิงลึก (Deep Response)
                        <span class="text-xs text-gray-500">— คำทำนายที่ลูกค้าจ่ายเงินซื้อ</span>
                    </label>
                    <textarea name="deep_response"
                              rows="20"
                              class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm leading-relaxed">{{ old('deep_response', $reading->deep_response) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ความยาวปัจจุบัน: {{ mb_strlen($reading->deep_response ?? '') }} ตัวอักษร (สูงสุด 50,000)
                    </p>
                </div>

                {{-- Basic Response --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        📝 คำทำนายพื้นฐาน (Basic Response)
                    </label>
                    <textarea name="basic_response"
                              rows="8"
                              class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">{{ old('basic_response', $reading->basic_response) }}</textarea>
                </div>

                {{-- AI Response (legacy) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        💬 AI Response (legacy)
                    </label>
                    <textarea name="ai_response"
                              rows="6"
                              class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">{{ old('ai_response', $reading->ai_response) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Admin Note --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                📝 บันทึกการแก้ไข (audit log)
            </label>
            <input type="text"
                   name="admin_note"
                   maxlength="500"
                   placeholder="เช่น: แก้คำทำนายเรื่องเงินตามที่ลูกค้าร้องเรียน, อัพเดทสถานะบิลจริง"
                   class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                บันทึกนี้จะถูกเก็บใน audit log พร้อมชื่อแอดมินที่แก้ + เวลา
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg shadow transition">
                <template x-if="!submitting">
                    <span>💾 บันทึกการแก้ไข</span>
                </template>
                <template x-if="submitting">
                    <span class="flex items-center gap-2">
                        <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                        กำลังบันทึก...
                    </span>
                </template>
            </button>

            <a href="{{ route('admin.fortune.readings.show', $reading) }}"
               class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ยกเลิก
            </a>
        </div>
    </form>

    {{-- Audit log --}}
    @php
        $audits = $reading->getConversationState('admin_edits', []);
    @endphp
    @if(! empty($audits))
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">📜 ประวัติการแก้ไข</h3>
            <ul class="space-y-2 text-sm">
                @foreach(array_reverse($audits) as $audit)
                    <li class="border-l-2 border-blue-400 pl-3">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($audit['edited_at'])->format('d/m/Y H:i:s') }}
                        </span>
                        — โดย <span class="font-medium">{{ $audit['edited_by'] }}</span>
                        @if(! empty($audit['note']))
                            <div class="text-gray-700 dark:text-gray-300 mt-1">📝 {{ $audit['note'] }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
