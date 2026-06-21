@extends('layouts.admin-v4')

@section('title', 'แก้ไขการทำนาย #' . $reading->id)

@section('content')
{{-- 🔮 หน้าแก้ไขการทำนาย — ธีม V4 นวลทองคำ
     คงฟอร์มเดิม 100%: action=readings.update + @method(PUT) + ชื่อ field/hidden ทุกตัว
     แปลง markup/สไตล์เป็น neumorphic ทองคำ — business logic ไม่แตะ --}}
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{ submitting: false }">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · ประวัติการทำนาย
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                แก้ไขการทำนาย #{{ $reading->id }} ✏️
            </h1>
            <p style="margin:6px 0 0; font-size:12.5px; color:var(--ink2);">
                ลูกค้า: <span style="font-weight:600; color:var(--ink);">{{ $reading->facebook_user_name ?? 'ไม่ระบุ' }}</span>
                · บิล: <span class="tp-num" style="font-size:12px;">{{ $reading->bill_reference ?? '-' }}</span>
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.readings.show', $reading) }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i> กลับไปดูรายละเอียด
            </a>
        </div>
    </div>

    {{-- ===== Validation errors ===== --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:flex-start; gap:10px;">
                <i class="fas fa-circle-exclamation" style="color:#d9534f; font-size:18px; margin-top:2px;"></i>
                <ul style="margin:0; padding-left:18px; font-size:13px; color:#d9534f; line-height:1.7;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ===== Cancellation banner (2026-05-25) — ถ้าบิลถูกยกเลิก ===== --}}
    @php
        $cancellationLabel = $reading->getCancellationReasonLabelOrNull();
        $cancellationReason = data_get($reading->conversation_state, 'cancellation_reason');
        $cancelledAt = data_get($reading->conversation_state, 'cancelled_at');
    @endphp
    @if($cancellationLabel)
        <div class="tp-card" style="padding:18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:22px; flex-shrink:0;
                            background:linear-gradient(135deg, #d9534f, #b34540);">❌</div>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:16px; font-weight:800; color:#d9534f;">{{ $cancellationLabel }}</h3>
                    <p style="margin:6px 0 0; font-size:12.5px; color:var(--ink2);">
                        <span style="font-weight:600;">เหตุผล:</span>
                        <code class="tp-num" style="font-size:11px; padding:2px 7px; border-radius:6px; box-shadow:var(--inset-sm);">{{ $cancellationReason }}</code>
                        @if($cancelledAt)
                            · <span style="font-weight:600;">เวลายกเลิก:</span> {{ \Carbon\Carbon::parse($cancelledAt)->format('Y-m-d H:i') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== ฟอร์มหลัก — คง action/method/@csrf/@method/field ทุกตัว ===== --}}
    <form action="{{ route('admin.fortune.readings.update', $reading) }}"
          method="POST"
          @submit="if(!confirm('ยืนยันการแก้ไขการทำนายนี้?')) { $event.preventDefault(); return; } submitting = true;">
        @csrf
        @method('PUT')

        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- ===== 💳 สถานะบิล ===== --}}
            <div class="tp-card" style="padding:24px;">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                    <div class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:17px;">💳</div>
                    <span style="font-size:16px; font-weight:800;">สถานะบิล</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                    {{-- สถานะ Conversation --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            สถานะ Conversation
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="conversation_status"
                                    style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
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
                    </div>

                    {{-- ชำระเงินแล้ว? --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            ชำระเงินแล้ว?
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="is_paid"
                                    style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
                                <option value="0" @selected(old('is_paid', $reading->is_paid) == false)>❌ ยังไม่ชำระ</option>
                                <option value="1" @selected(old('is_paid', $reading->is_paid) == true)>✅ ชำระแล้ว</option>
                            </select>
                        </div>
                    </div>

                    {{-- จำนวนเงิน --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            จำนวนเงิน (฿)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" name="amount_paid" step="0.01" min="0"
                                   value="{{ old('amount_paid', $reading->amount_paid) }}"
                                   style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
                        </div>
                    </div>

                    {{-- วันที่ชำระ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            วันที่ชำระ
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="datetime-local" name="paid_at"
                                   value="{{ old('paid_at', $reading->paid_at?->format('Y-m-d\TH:i')) }}"
                                   style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
                        </div>
                        <p style="margin:6px 0 0; font-size:11px; color:var(--ink2);">
                            ถ้าเลือก "ชำระแล้ว" แต่ปล่อยว่าง → ระบบใช้เวลาปัจจุบัน
                        </p>
                    </div>
                </div>
            </div>

            {{-- ===== 📋 ข้อมูลลูกค้า (Admin Edit) — โทนทองอ่อนเน้น ===== --}}
            <div class="tp-card" style="padding:24px; border-left:4px solid var(--accent1);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <div class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:17px;">📋</div>
                    <span style="font-size:16px; font-weight:800;">ข้อมูลลูกค้า (Admin Edit)</span>
                </div>
                <p style="margin:0 0 18px 48px; font-size:12px; color:var(--ink2);">
                    💡 ใช้กรณีลูกค้ากรอกผิด/ค้าง — admin แก้แทนได้ทั้ง วันเกิด คำถาม และจับไพ่
                </p>

                <div style="display:flex; flex-direction:column; gap:18px;">
                    {{-- วันเกิด --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            📅 วันเกิด (ค.ศ.)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="date" name="birth_date"
                                   value="{{ old('birth_date', $reading->birth_date?->format('Y-m-d')) }}"
                                   style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
                        </div>
                        <p style="margin:6px 0 0; font-size:11px; color:var(--ink2);">
                            ปัจจุบัน: {{ $reading->birth_date ? $reading->birth_date->format('d M Y (ค.ศ.)') : '❌ ยังไม่กรอก' }}
                        </p>
                    </div>

                    {{-- คำถาม --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            ❓ คำถาม (1 บรรทัด/1 คำถาม)
                        </label>
                        @php
                            $currentQuestions = is_array($reading->questions) ? $reading->questions : [];
                            $questionsText = implode("\n", $currentQuestions);
                        @endphp
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="questions_input" rows="5"
                                      placeholder="ดวงความรักปีนี้จะเป็นยังไง&#10;งานจะมั่นคงไหม&#10;การเงินช่วงนี้"
                                      style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:13px; line-height:1.6; resize:vertical;">{{ old('questions_input', $questionsText) }}</textarea>
                        </div>
                        <p style="margin:6px 0 0; font-size:11px; color:var(--ink2);">
                            ปัจจุบัน: {{ count($currentQuestions) }} คำถาม
                        </p>
                    </div>

                    {{-- ไพ่ที่จับ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            🃏 ไพ่ที่จับ
                        </label>
                        @php
                            $tarotCards = $reading->getCollectedTarotCards();
                        @endphp
                        @if(count($tarotCards) > 0)
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                @foreach($tarotCards as $idx => $card)
                                    <div style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:10px; box-shadow:var(--inset-sm); font-size:13px; color:var(--ink);">
                                        <span>🃏</span>
                                        <span class="tp-num" style="font-size:12px; color:var(--deep1);">#{{ $idx + 1 }}</span>
                                        <span style="font-weight:600;">{{ $card['card_name_th'] ?? '?' }}</span>
                                        <span style="color:var(--ink2);">({{ $card['card_name_en'] ?? '?' }})</span>
                                        @if(! empty($card['is_reversed']))
                                            <span class="tp-pill" style="background:#d6824a; color:#fff; font-size:10px; padding:2px 8px;">กลับหัว</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="padding:12px 14px; border-radius:10px; box-shadow:var(--inset-sm);">
                                <p style="margin:0 0 10px; font-size:13px; color:#d9534f;">❌ ยังไม่ได้จับไพ่</p>
                                <label style="display:inline-flex; align-items:center; gap:9px; cursor:pointer; font-size:13px; color:var(--ink);">
                                    <input type="checkbox" name="pick_tarot_random" value="1"
                                           style="width:16px; height:16px; accent-color:var(--accent1);">
                                    <span>🎲 จับไพ่ random ให้ตอน save (admin manual pick)</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                @if($reading->is_paid && empty($reading->deep_response))
                    <div style="margin-top:18px; padding:13px 15px; border-radius:12px; box-shadow:var(--inset-sm); border-left:3px solid #5689b8;">
                        <p style="margin:0; font-size:12.5px; color:var(--ink); line-height:1.6;">
                            💡 <strong>หลังบันทึกข้อมูลครบ</strong> (วันเกิด + คำถาม + ไพ่)
                            → กลับไปหน้า show แล้วกดปุ่ม <strong>"🔄 สร้างคำทำนายเชิงลึก"</strong> เพื่อ trigger AI
                        </p>
                    </div>
                @endif
            </div>

            {{-- ===== 🔮 คำทำนาย ===== --}}
            <div class="tp-card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                    <div class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:17px;">🔮</div>
                    <span style="font-size:16px; font-weight:800;">คำทำนาย</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:18px;">
                    {{-- Deep Response — คำทำนายที่ลูกค้าจ่ายเงินซื้อ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            🌟 คำทำนายเชิงลึก (Deep Response)
                            <span style="font-size:11px; color:var(--deep1);">— คำทำนายที่ลูกค้าจ่ายเงินซื้อ</span>
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="deep_response" rows="20"
                                      class="tp-num"
                                      style="width:100%; background:transparent; border:0; outline:0; padding:13px; color:var(--ink); font-size:13px; line-height:1.7; resize:vertical;">{{ old('deep_response', $reading->deep_response) }}</textarea>
                        </div>
                        <p style="margin:6px 0 0; font-size:11px; color:var(--ink2);">
                            ความยาวปัจจุบัน: {{ mb_strlen($reading->deep_response ?? '') }} ตัวอักษร (สูงสุด 50,000)
                        </p>
                    </div>

                    {{-- Basic Response --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            📝 คำทำนายพื้นฐาน (Basic Response)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="basic_response" rows="8"
                                      style="width:100%; background:transparent; border:0; outline:0; padding:13px; color:var(--ink); font-size:13px; line-height:1.6; resize:vertical;">{{ old('basic_response', $reading->basic_response) }}</textarea>
                        </div>
                    </div>

                    {{-- AI Response (legacy) --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                            💬 AI Response (legacy)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="ai_response" rows="6"
                                      style="width:100%; background:transparent; border:0; outline:0; padding:13px; color:var(--ink); font-size:13px; line-height:1.6; resize:vertical;">{{ old('ai_response', $reading->ai_response) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== 📝 บันทึกการแก้ไข (audit log) ===== --}}
            <div class="tp-card" style="padding:24px;">
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    📝 บันทึกการแก้ไข (audit log)
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="admin_note" maxlength="500"
                           placeholder="เช่น: แก้คำทำนายเรื่องเงินตามที่ลูกค้าร้องเรียน, อัพเดทสถานะบิลจริง"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px;">
                </div>
                <p style="margin:6px 0 0; font-size:11px; color:var(--ink2);">
                    บันทึกนี้จะถูกเก็บใน audit log พร้อมชื่อแอดมินที่แก้ + เวลา
                </p>
            </div>

            {{-- ===== ปุ่ม action ===== --}}
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <button type="submit" :disabled="submitting" class="tp-btn tp-btn-primary"
                        style="min-width:180px;">
                    <template x-if="!submitting">
                        <span><i class="fas fa-floppy-disk"></i> บันทึกการแก้ไข</span>
                    </template>
                    <template x-if="submitting">
                        <span style="display:inline-flex; align-items:center; gap:8px;">
                            <i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...
                        </span>
                    </template>
                </button>

                <a href="{{ route('admin.fortune.readings.show', $reading) }}" class="tp-btn">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>

    {{-- ===== 📜 ประวัติการแก้ไข ===== --}}
    @php
        $audits = $reading->getConversationState('admin_edits', []);
    @endphp
    @if(! empty($audits))
        <div class="tp-card" style="padding:24px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                <div class="tp-tile" style="width:34px; height:34px; border-radius:10px; font-size:15px;">📜</div>
                <span style="font-size:15px; font-weight:800;">ประวัติการแก้ไข</span>
            </div>
            <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px;">
                @foreach(array_reverse($audits) as $audit)
                    <li style="padding:11px 14px; border-radius:11px; box-shadow:var(--inset-sm); border-left:3px solid var(--accent1);">
                        <div style="font-size:12.5px; color:var(--ink);">
                            <span class="tp-num" style="font-size:12px; color:var(--ink2);">
                                {{ \Carbon\Carbon::parse($audit['edited_at'])->format('d/m/Y H:i:s') }}
                            </span>
                            — โดย <span style="font-weight:700; color:var(--deep1);">{{ $audit['edited_by'] }}</span>
                        </div>
                        @if(! empty($audit['note']))
                            <div style="margin-top:5px; font-size:12.5px; color:var(--ink2);">📝 {{ $audit['note'] }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
