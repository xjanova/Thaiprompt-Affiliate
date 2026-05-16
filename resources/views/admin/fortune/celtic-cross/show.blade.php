{{-- Celtic Cross Reading Detail (admin view) --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="text-sm text-purple-600 hover:underline">← กลับไปหน้ารวม</a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                🔮 Celtic Cross #{{ $reading->id }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Bill: <code>{{ $reading->bill_reference ?? '-' }}</code> •
                {{ $reading->facebook_user_name ?? '-' }} •
                {{ $reading->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($reading->celtic_summary_image_path)
                <a href="{{ asset('storage/' . $reading->celtic_summary_image_path) }}" target="_blank"
                   class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm">
                    🖼️ เปิดภาพ Spread
                </a>
            @endif
            @if($reading->is_paid)
                {{-- 🔄 (2026-05-16) Restore active chat — เปิด Pro Session กลับให้ลูกค้าคุยต่อ --}}
                @if($reading->getCelticPickedCount() >= 10)
                    <form action="{{ route('admin.fortune.celtic-cross.restore', $reading) }}" method="POST"
                          onsubmit="return confirm('ยืนยันคืนสถานะ &quot;กำลังดูอยู่&quot; ให้ #{{ $reading->id }}?\n\nลูกค้าจะกลับมาคุยกับแม่หมอต่อได้ตามเวลาที่เหลือ\n(ถ้าหมดเวลาแล้ว ระบบจะ reset window ใหม่)');"
                          class="inline">
                        @csrf
                        <input type="hidden" name="notify" value="1">
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 rounded-lg text-sm font-semibold">
                            🔄 คืนสถานะกำลังดู + แจ้งลูกค้า
                        </button>
                    </form>
                @endif

                {{-- 🔄 (2026-05-03) Admin reset — ใช้เมื่อ flow ไม่สมบูรณ์ --}}
                <form action="{{ route('admin.fortune.celtic-cross.reset', $reading) }}" method="POST"
                      onsubmit="return confirm('ยืนยัน reset reading #{{ $reading->id }}?\n\nจะล้างไพ่ + Q&A ทั้งหมด แล้วให้ลูกค้าเริ่มเปิดไพ่ใหม่ (ไม่ต้องจ่ายซ้ำ)');"
                      class="inline">
                    @csrf
                    <input type="hidden" name="notify" value="1">
                    <button type="submit"
                            class="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/50 rounded-lg text-sm font-semibold">
                        🔄 Reset + แจ้งลูกค้า
                    </button>
                </form>
            @else
                {{-- 🚀 (2026-05-08) Force Approve — โอนยอดไม่ตรง → admin มาร์คจ่าย + push เริ่มเปิดไพ่ --}}
                <div x-data="{ open: false, amount: '{{ $reading->amount_paid ?? 99 }}' }" class="inline-block">
                    <button @click="open = true" type="button"
                            class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 rounded-lg text-sm font-semibold">
                        🚀 Force Approve (โอนไม่ตรงยอด)
                    </button>

                    {{-- Modal --}}
                    <div x-show="open" x-cloak
                         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                         @click.self="open = false">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                🚀 Force Approve บิล {{ $reading->bill_reference ?? '#' . $reading->id }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                ใช้กรณีลูกค้าโอนยอดไม่ตรง (เช่น 99 แทน 99.37) → SMS app จับคู่ไม่ได้<br>
                                ระบบจะ <strong>มาร์คบิลจ่ายแล้ว</strong> + <strong>ส่งให้ลูกค้าเริ่มเปิดไพ่ทันที</strong>
                            </p>

                            <form action="{{ route('admin.fortune.celtic-cross.force-approve', $reading) }}" method="POST">
                                @csrf
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    จำนวนเงินที่ลูกค้าโอนจริง (บาท)
                                </label>
                                <input type="number" name="actual_amount" x-model="amount"
                                       step="0.01" min="0.01" max="9999" required
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:outline-none">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ยอดบิลที่ตั้งไว้: ฿{{ number_format($reading->amount_paid ?? 99, 2) }}
                                </p>

                                <div class="flex gap-2 mt-5">
                                    <button @click="open = false" type="button"
                                            class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600">
                                        ยกเลิก
                                    </button>
                                    <button type="submit"
                                            onclick="return confirm('ยืนยัน? ระบบจะมาร์คบิลจ่ายแล้ว + ส่งให้ลูกค้าเริ่มเปิดไพ่ — ย้อนกลับไม่ได้');"
                                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold">
                                        🚀 ยืนยัน Force Approve
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- 🗑️ (2026-05-04) Admin cancel — ลบบิลที่ขัดกัน (pending payment ค้าง) — ปลอดภัยถ้ายังไม่จ่าย --}}
                @if((int) ($reading->celtic_questions_used ?? 0) === 0)
                    <form action="{{ route('admin.fortune.celtic-cross.cancel', $reading) }}" method="POST"
                          onsubmit="return confirm('ยืนยันยกเลิกบิล {{ $reading->bill_reference ?? '#' . $reading->id }}?\n\nจะปลด UPA + ปิด conversation (ปลอดภัยเพราะยังไม่จ่าย)');"
                          class="inline">
                        @csrf
                        <input type="hidden" name="notify" value="1">
                        <button type="submit"
                                class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg text-sm font-semibold">
                            🗑️ ยกเลิกบิล + แจ้งลูกค้า
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-6 rounded-r-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6 rounded-r-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Status Card --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">สถานะ</p>
            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ $reading->conversation_status }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">ค่าครู</p>
            <p class="text-base font-bold {{ $reading->is_paid ? 'text-green-600' : 'text-gray-400' }} mt-1">
                {{ $reading->is_paid ? '฿' . number_format($reading->amount_paid, 0) . ' ✓' : 'รอ' }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">คำถามใช้ไป</p>
            <p class="text-base font-bold text-purple-600 mt-1">{{ $reading->celtic_questions_used }}/{{ $maxQuestions > 0 ? $maxQuestions : '∞' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Q1 ตอบเมื่อ</p>
            <p class="text-base font-bold text-gray-900 dark:text-white mt-1 text-xs">
                {{ $reading->celtic_first_answered_at?->format('d/m H:i') ?? '—' }}
            </p>
        </div>
    </div>

    {{-- 🤖 (2026-05-16) Admin Ask AI — แอดมินพิมพ์คำถามให้ AI ทำนายแทนลูกค้า + push อัตโนมัติ --}}
    @if($reading->is_paid && $reading->getCelticPickedCount() >= 10 && $reading->canAskMoreCeltic())
        @php
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $questionsUsed = (int) $reading->celtic_questions_used;
            $questionsLeft = $maxQuestions > 0 ? max(0, $maxQuestions - $questionsUsed) : null;
        @endphp
        <div x-data="{
                question: '',
                submitting: false,
                charCount: 0,
                handleInput(e) { this.question = e.target.value; this.charCount = this.question.length; },
                submit(e) {
                    if (this.question.trim().length < 3) {
                        e.preventDefault();
                        alert('กรุณาพิมพ์คำถามอย่างน้อย 3 ตัวอักษร');
                        return false;
                    }
                    if (!confirm('ยืนยันส่งคำถามให้ AI ทำนาย?\n\nคำถาม: ' + this.question.substring(0, 200) + (this.question.length > 200 ? '...' : '') + '\n\n⚠️ จะนับเป็นคำถามของลูกค้า (sequence Q' + ({{ $questionsUsed + 1 }}) + ') + ส่ง LINE/FB อัตโนมัติทันที')) {
                        e.preventDefault();
                        return false;
                    }
                    this.submitting = true;
                }
             }"
             class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl shadow-lg p-6 mb-6 border border-indigo-200 dark:border-indigo-800">
            <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🤖 Admin Ask AI
                        <span class="text-xs px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full font-normal">
                            ทำนายแทนลูกค้า
                        </span>
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        แอดมินพิมพ์คำถาม → AI สร้างคำทำนาย → ส่งให้ลูกค้าทาง {{ strtoupper($reading->platform ?? 'LINE/FB') }} อัตโนมัติ
                    </p>
                </div>
                <div class="text-right text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    @if($questionsLeft !== null)
                        <div>💬 เหลือ <strong class="text-indigo-600 dark:text-indigo-400">{{ $questionsLeft }}</strong> คำถาม</div>
                    @else
                        <div>💬 ไม่จำกัดจำนวน</div>
                    @endif
                    @if($remainingMin !== null)
                        <div>⏳ เหลือเวลา <strong class="text-indigo-600 dark:text-indigo-400">{{ $remainingMin }}</strong> นาที</div>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.fortune.celtic-cross.ask-ai', $reading) }}" method="POST"
                  @submit="submit($event)">
                @csrf
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    📝 คำถามที่จะส่งให้ AI ทำนาย (สูงสุด 1000 ตัวอักษร)
                </label>
                <textarea name="question"
                          rows="3"
                          maxlength="1000"
                          required
                          :disabled="submitting"
                          @input="handleInput($event)"
                          placeholder="เช่น: ความรักของฉันในเดือนนี้จะเป็นอย่างไร?"
                          class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:opacity-50 resize-none"></textarea>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="charCount">0</span>/1000 ตัวอักษร
                    </span>
                    <button type="submit"
                            :disabled="submitting || charCount < 3"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all">
                        <span x-show="!submitting">🚀 ส่งให้ AI ทำนาย + แจ้งลูกค้า</span>
                        <span x-show="submitting" x-cloak>⏳ กำลังประมวลผล...</span>
                    </button>
                </div>
            </form>

            <p class="text-xs text-amber-700 dark:text-amber-400 mt-3 flex items-start gap-1">
                <span>⚠️</span>
                <span>คำถามนี้จะถูกนับเป็น Q{{ $questionsUsed + 1 }} ของลูกค้า + AI จะใช้บริบทไพ่ 10 ใบที่เปิดไว้ในการทำนาย — คำตอบส่งทันทีไม่มี preview</span>
            </p>
        </div>
    @endif

    {{-- Spread Image --}}
    @if($reading->celtic_summary_image_path)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🖼️ Celtic Cross Spread</h2>
            <img src="{{ asset('storage/' . $reading->celtic_summary_image_path) }}"
                 alt="Celtic Cross Spread"
                 class="max-w-full mx-auto rounded-lg shadow">
        </div>
    @endif

    {{-- 10 Cards --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🃏 ไพ่ทั้ง 10 ใบ</h2>

        @if(empty($cards))
            <p class="text-center text-gray-500 py-4">ยังไม่ได้เลือกไพ่</p>
        @else
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                @for($pos = 1; $pos <= 10; $pos++)
                    @php $card = $cards[$pos] ?? null; @endphp
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold mb-1">
                            [{{ $pos }}] {{ $positions[$pos]['name'] ?? '?' }}
                        </p>
                        @if($card)
                            @if(!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" alt="{{ $card['card_name_th'] ?? '' }}"
                                     class="w-full rounded mb-2 {{ !empty($card['is_reversed']) ? 'rotate-180' : '' }}">
                            @endif
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $card['card_name_th'] ?? '?' }}</p>
                            <p class="text-xs text-gray-500">{{ $card['card_name_en'] ?? '' }}</p>
                            <p class="text-xs {{ !empty($card['is_reversed']) ? 'text-red-600' : 'text-green-600' }} mt-1">
                                {{ !empty($card['is_reversed']) ? '⤵ กลับหัว' : '↑ ตั้งตรง' }}
                            </p>
                        @else
                            <p class="text-xs text-gray-400 italic mt-2">ยังไม่เลือก</p>
                        @endif
                    </div>
                @endfor
            </div>
        @endif
    </div>

    {{-- Q&A History --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💬 คำถาม & คำตอบ</h2>

        @if($reading->celticQuestions->isEmpty())
            <p class="text-center text-gray-500 py-4">ยังไม่มีคำถาม</p>
        @else
            @foreach($reading->celticQuestions as $q)
                <div class="border-l-4 {{ $q->sequence === 1 ? 'border-purple-500' : 'border-blue-400' }} bg-gray-50 dark:bg-gray-900/50 p-4 mb-4 rounded-r-lg">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs font-semibold">
                            Q{{ $q->sequence }} {{ $q->sequence === 1 ? '(Main)' : '(Follow-up)' }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ $q->answered_at?->format('d/m H:i:s') ?? 'รอ' }}
                            @if($q->ai_response_time_ms) • {{ round($q->ai_response_time_ms / 1000, 1) }}s @endif
                            @if($q->ai_tokens_used) • {{ number_format($q->ai_tokens_used) }} tokens @endif
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">❓ {{ $q->question }}</p>

                    @if($q->response)
                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans bg-white dark:bg-gray-800 p-3 rounded mt-2">{{ $q->response }}</pre>
                    @else
                        <p class="text-xs text-red-500 italic">ยังไม่มีคำตอบ (อาจ AI ล้มเหลว)</p>
                    @endif

                    @if($q->ai_provider)
                        <p class="text-xs text-gray-500 mt-2">via {{ $q->ai_provider }} • {{ $q->ai_model }}</p>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
